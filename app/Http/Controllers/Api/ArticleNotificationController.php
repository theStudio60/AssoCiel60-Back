<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\User;
use App\Models\ActivityLog;
use App\Mail\NewArticleMail;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Log;

class ArticleNotificationController extends Controller
{
    /**
     * Reçoit le webhook WordPress quand un nouvel article est publié
     * et envoie un email à tous les membres actifs
     */
    public function notifyNewArticle(Request $request)
    {
        // Sécurité : vérifier le token secret
        $secret = $request->header('X-Webhook-Secret');
        if ($secret !== env('WORDPRESS_WEBHOOK_SECRET')) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized'
            ], 401);
        }

        // Validation des données
        $request->validate([
            'title' => 'required|string',
            'url' => 'required|url',
            'excerpt' => 'nullable|string',
            'date' => 'nullable|string',
        ]);

        $article = [
            'title' => $request->title,
            'url' => $request->url,
            'excerpt' => $request->excerpt ?? '',
            'date' => $request->date ?? now(),
        ];

        try {
            // Récupérer tous les membres actifs avec un abonnement actif
            $users = User::whereHas('organization.subscriptions', function($q) {
                $q->where('status', 'active');
            })->where('status', 'active')->get();

            $sentCount = 0;
            $errorCount = 0;

            foreach ($users as $user) {
                try {
                    Mail::to($user->email)->send(new NewArticleMail($user, $article));
                    $sentCount++;
                } catch (\Exception $e) {
                    Log::error("Erreur envoi email à {$user->email}: " . $e->getMessage());
                    $errorCount++;
                }
            }

            // Log de l'activité
            ActivityLog::log(
                'article_notification_sent',
                "Notification nouvel article envoyée : {$article['title']}",
                null, null,
                [
                    'article_title' => $article['title'],
                    'sent_count' => $sentCount,
                    'error_count' => $errorCount,
                ]
            );

            return response()->json([
                'success' => true,
                'message' => 'Emails envoyés avec succès',
                'sent_count' => $sentCount,
                'error_count' => $errorCount,
            ]);

        } catch (\Exception $e) {
            Log::error('Erreur notification article: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 500);
        }
    }
}