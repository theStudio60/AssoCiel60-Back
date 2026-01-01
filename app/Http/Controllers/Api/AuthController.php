<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;
use Illuminate\Support\Facades\Mail;
use App\Mail\TwoFactorCode;

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

            // Pour test, log le code
            \Log::info("2FA Code for {$user->email}: {$code}");

            return response()->json([
                'requires_2fa' => true,
                'user_id' => $user->id,
                'debug_code' => $code, // REMOVE in production
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