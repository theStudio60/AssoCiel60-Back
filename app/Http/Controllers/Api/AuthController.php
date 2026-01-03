<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use App\Mail\TwoFactorCode;
use App\Mail\PasswordResetMail;

class AuthController extends Controller
{
    public function login(Request $request)
    {
        $request->validate([
            'email' => 'required|email',
            'password' => 'required',
        ]);

        $user = User::where('email', $request->email)->first();

        if (!$user || !Hash::check($request->password, $user->password)) {
            throw ValidationException::withMessages([
                'email' => ['The provided credentials are incorrect.'],
            ]);
        }

        if ($user->two_factor_enabled) {
            $code = str_pad(random_int(0, 999999), 6, '0', STR_PAD_LEFT);
            
            $user->update([
                'two_factor_code' => $code,
                'two_factor_expires_at' => now()->addMinutes(10),
            ]);

            try {
                Mail::to($user->email)->send(new TwoFactorCode($code, $user->name));
                \Log::info("2FA email sent to {$user->email}");
            } catch (\Exception $e) {
                \Log::error("Failed to send 2FA email: " . $e->getMessage());
            }

            \Log::info("2FA Code for {$user->email}: {$code}");

            return response()->json([
                'requires_2fa' => true,
                'user_id' => $user->id,
                'debug_code' => $code,
            ]);
        }

        $token = $user->createToken('auth-token')->plainTextToken;

        return response()->json([
            'user' => $user->load('organization'),
            'token' => $token,
        ]);
    }

    public function verify2FA(Request $request)
    {
        $request->validate([
            'user_id' => 'required',
            'code' => 'required|digits:6',
        ]);

        $user = User::findOrFail($request->user_id);

        if (!$user->two_factor_code || 
            $user->two_factor_code !== $request->code || 
            $user->two_factor_expires_at < now()) {
            return response()->json(['message' => 'Code invalide ou expiré'], 401);
        }

        $user->update([
            'two_factor_code' => null,
            'two_factor_expires_at' => null,
        ]);

        $token = $user->createToken('auth-token')->plainTextToken;

        return response()->json([
            'user' => $user->load('organization'),
            'token' => $token,
        ]);
    }

    /**
     * Envoyer un email de réinitialisation de mot de passe
     */
    public function forgotPassword(Request $request)
    {
        $request->validate([
            'email' => 'required|email',
        ]);

        $user = User::where('email', $request->email)->first();

        if (!$user) {
            // Pour des raisons de sécurité, on retourne toujours success
            // même si l'email n'existe pas
            return response()->json([
                'success' => true,
                'message' => 'Si cet email existe, un lien de réinitialisation a été envoyé'
            ]);
        }

        // Supprimer les anciens tokens pour cet email
        DB::table('password_reset_tokens')->where('email', $user->email)->delete();

        // Créer un nouveau token
        $token = Str::random(60);

        // Sauvegarder le token
        DB::table('password_reset_tokens')->insert([
            'email' => $user->email,
            'token' => Hash::make($token),
            'created_at' => now(),
        ]);

        // Envoyer l'email
        try {
            Mail::to($user->email)->send(new PasswordResetMail($token, $user));
            \Log::info("Password reset email sent to {$user->email}");
        } catch (\Exception $e) {
            \Log::error("Failed to send password reset email: " . $e->getMessage());
            
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de l\'envoi de l\'email'
            ], 500);
        }

        return response()->json([
            'success' => true,
            'message' => 'Un email de réinitialisation a été envoyé'
        ]);
    }

    /**
     * Réinitialiser le mot de passe
     */
    public function resetPassword(Request $request)
    {
        $request->validate([
            'email' => 'required|email',
            'token' => 'required',
            'password' => 'required|min:8|confirmed',
        ]);

        // Récupérer le token
        $resetRecord = DB::table('password_reset_tokens')
            ->where('email', $request->email)
            ->first();

        if (!$resetRecord) {
            return response()->json([
                'success' => false,
                'message' => 'Token invalide ou expiré'
            ], 400);
        }

        // Vérifier le token
        if (!Hash::check($request->token, $resetRecord->token)) {
            return response()->json([
                'success' => false,
                'message' => 'Token invalide'
            ], 400);
        }

        // Vérifier que le token n'est pas expiré (60 minutes)
        if (now()->diffInMinutes($resetRecord->created_at) > 60) {
            DB::table('password_reset_tokens')->where('email', $request->email)->delete();
            
            return response()->json([
                'success' => false,
                'message' => 'Token expiré. Veuillez faire une nouvelle demande.'
            ], 400);
        }

        // Mettre à jour le mot de passe
        $user = User::where('email', $request->email)->first();

        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'Utilisateur non trouvé'
            ], 404);
        }

        $user->update([
            'password' => Hash::make($request->password),
        ]);

        // Supprimer le token
        DB::table('password_reset_tokens')->where('email', $request->email)->delete();

        // Logger l'activité
        \App\Models\ActivityLog::log(
            'password_reset',
            "Mot de passe réinitialisé pour {$user->first_name} {$user->last_name}",
            User::class,
            $user->id
        );

        return response()->json([
            'success' => true,
            'message' => 'Mot de passe réinitialisé avec succès'
        ]);
    }

    public function logout(Request $request)
    {
        $request->user()->currentAccessToken()->delete();

        return response()->json(['message' => 'Logged out successfully']);
    }

    public function me(Request $request)
    {
        return response()->json($request->user()->load('organization'));
    }
}