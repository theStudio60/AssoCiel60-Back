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
    public function sendResetLinkByAdmin(Request $request, $userId)
    {
        try {
            $user = User::findOrFail($userId);
            $token = Str::random(64);
            
            Log::info('Generating password reset token for user: ' . $user->email);
            
            DB::table('password_reset_tokens')->where('email', $user->email)->delete();
            
            DB::table('password_reset_tokens')->insert([
                'email' => $user->email,
                'token' => Hash::make($token),
                'created_at' => now()
            ]);
            
            $resetUrl = env('FRONTEND_URL') . '/reset-password?token=' . $token . '&email=' . urlencode($user->email);
            
            Mail::send('emails.password-reset', [
                'resetUrl' => $resetUrl, 
                'userName' => $user->first_name . ' ' . $user->last_name
            ], function($message) use ($user) {
                $message->to($user->email);
                $message->subject('Réinitialisation de votre mot de passe - Alprail');
            });
            
            return response()->json([
                'success' => true,
                'message' => 'Email de réinitialisation envoyé avec succès'
            ]);
        } catch (\Exception $e) {
            Log::error('Password reset send error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de l\'envoi de l\'email: ' . $e->getMessage()
            ], 500);
        }
    }
    
    public function resetPassword(Request $request)
    {
        $request->validate([
            'email' => 'required|email',
            'token' => 'required',
            'password' => 'required|min:8|confirmed',
        ]);
        
        try {
            $email = urldecode($request->email);
            
            Log::info('=== PASSWORD RESET ATTEMPT ===');
            Log::info('Email: ' . $email);
            
            $passwordReset = DB::table('password_reset_tokens')
                ->where('email', $email)
                ->first();
            
            if (!$passwordReset) {
                return response()->json([
                    'success' => false,
                    'message' => 'Aucune demande de réinitialisation trouvée pour cet email'
                ], 400);
            }
            
            if (!Hash::check($request->token, $passwordReset->token)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Le lien de réinitialisation est invalide'
                ], 400);
            }
            
            $createdAt = \Carbon\Carbon::parse($passwordReset->created_at);
            $hoursDiff = now()->diffInHours($createdAt);
            
            if ($hoursDiff > 1) {
                return response()->json([
                    'success' => false,
                    'message' => 'Le lien a expiré (valable 1h). Veuillez demander un nouveau lien.'
                ], 400);
            }
            
            $user = User::where('email', $email)->first();
            
            if (!$user) {
                return response()->json([
                    'success' => false,
                    'message' => 'Utilisateur non trouvé'
                ], 404);
            }
            
            $user->password = Hash::make($request->password);
            $user->save();
            
            DB::table('password_reset_tokens')->where('email', $email)->delete();
            
            return response()->json([
                'success' => true,
                'message' => 'Mot de passe changé avec succès'
            ]);
        } catch (\Exception $e) {
            Log::error('Password reset error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la réinitialisation: ' . $e->getMessage()
            ], 500);
        }
    }
}