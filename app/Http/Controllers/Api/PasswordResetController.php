<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Log;

class PasswordResetController extends Controller
{
    /**
     * Admin envoie un email de réinitialisation
     */
    public function sendResetLinkByAdmin(Request $request, $userId)
    {
        try {
            $user = User::findOrFail($userId);
            
            // Générer un token
            $token = Str::random(64);
            
            Log::info('Generating password reset token for user: ' . $user->email);
            
            // Supprimer les anciens tokens
            DB::table('password_resets')->where('email', $user->email)->delete();
            
            // Créer un nouveau token
            DB::table('password_resets')->insert([
                'email' => $user->email,
                'token' => Hash::make($token),
                'created_at' => now()
            ]);
            
            Log::info('Token created and saved to database');
            
            // URL de réinitialisation
            $resetUrl = env('FRONTEND_URL') . '/reset-password?token=' . $token . '&email=' . urlencode($user->email);
            
            // Envoyer l'email
            Mail::send('emails.password-reset', [
                'resetUrl' => $resetUrl, 
                'userName' => $user->first_name . ' ' . $user->last_name
            ], function($message) use ($user) {
                $message->to($user->email);
                $message->subject('Réinitialisation de votre mot de passe - Alprail');
            });
            
            Log::info('Reset email sent successfully to: ' . $user->email);
            
            return response()->json([
                'success' => true,
                'message' => 'Email de réinitialisation envoyé avec succès'
            ]);
        } catch (\Exception $e) {
            Log::error('Password reset send error: ' . $e->getMessage());
            Log::error($e->getTraceAsString());
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de l\'envoi de l\'email: ' . $e->getMessage()
            ], 500);
        }
    }
    
    /**
     * Membre réinitialise son mot de passe
     */
    public function resetPassword(Request $request)
    {
        $request->validate([
            'email' => 'required|email',
            'token' => 'required',
            'password' => 'required|min:8|confirmed',
        ]);
        
        try {
            // Décoder l'email au cas où il serait encodé
            $email = urldecode($request->email);
            
            Log::info('=== PASSWORD RESET ATTEMPT ===');
            Log::info('Email: ' . $email);
            Log::info('Token length: ' . strlen($request->token));
            
            // Vérifier le token dans la base de données
            $passwordReset = DB::table('password_resets')
                ->where('email', $email)
                ->first();
            
            if (!$passwordReset) {
                Log::error('No password reset record found for email: ' . $email);
                
                // Debug: Afficher tous les emails en base
                $allResets = DB::table('password_resets')->get();
                Log::info('All password resets in DB: ', $allResets->toArray());
                
                return response()->json([
                    'success' => false,
                    'message' => 'Aucune demande de réinitialisation trouvée pour cet email'
                ], 400);
            }
            
            Log::info('Password reset record found');
            Log::info('Stored token (hashed): ' . substr($passwordReset->token, 0, 20) . '...');
            Log::info('Created at: ' . $passwordReset->created_at);
            
            // Vérifier que le token correspond
            if (!Hash::check($request->token, $passwordReset->token)) {
                Log::error('Token mismatch - Hash check failed');
                return response()->json([
                    'success' => false,
                    'message' => 'Le lien de réinitialisation est invalide'
                ], 400);
            }
            
            Log::info('Token verified successfully');
            
            // Vérifier que le token n'est pas expiré (1 heure)
            $createdAt = \Carbon\Carbon::parse($passwordReset->created_at);
            $hoursDiff = now()->diffInHours($createdAt);
            
            Log::info('Token age in hours: ' . $hoursDiff);
            
            if ($hoursDiff > 1) {
                Log::error('Token expired - created ' . $hoursDiff . ' hours ago');
                return response()->json([
                    'success' => false,
                    'message' => 'Le lien a expiré (valable 1h). Veuillez demander un nouveau lien.'
                ], 400);
            }
            
            // Changer le mot de passe
            $user = User::where('email', $email)->first();
            
            if (!$user) {
                Log::error('User not found with email: ' . $email);
                return response()->json([
                    'success' => false,
                    'message' => 'Utilisateur non trouvé'
                ], 404);
            }
            
            Log::info('Updating password for user: ' . $user->email);
            
            $user->password = Hash::make($request->password);
            $user->save();
            
            Log::info('Password updated successfully');
            
            // Supprimer le token
            DB::table('password_resets')->where('email', $email)->delete();
            
            Log::info('Token deleted from database');
            Log::info('=== PASSWORD RESET SUCCESS ===');
            
            return response()->json([
                'success' => true,
                'message' => 'Mot de passe changé avec succès'
            ]);
        } catch (\Exception $e) {
            Log::error('=== PASSWORD RESET ERROR ===');
            Log::error('Error message: ' . $e->getMessage());
            Log::error('Stack trace: ' . $e->getTraceAsString());
            
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la réinitialisation: ' . $e->getMessage()
            ], 500);
        }
    }
}