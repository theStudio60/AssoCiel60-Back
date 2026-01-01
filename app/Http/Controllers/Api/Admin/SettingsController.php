<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class SettingsController extends Controller
{
    /**
     * Get all settings
     */
    public function index()
    {
        try {
            // Pour l'instant, on retourne des settings par défaut
            // Plus tard, on créera une table 'settings' dans la DB
            $settings = [
                'site_name' => 'Alprail',
                'site_email' => 'contact@alprail.net',
                'site_phone' => '+41 21 555 00 00',
                'site_address' => 'Route de Lausanne 1, 1000 Lausanne, Suisse',
                'currency' => 'CHF',
                'tax_rate' => '0',
                'invoice_prefix' => 'INV-',
                'enable_2fa' => true,
                'enable_newsletter' => true,
                'maintenance_mode' => false,
            ];

            return response()->json([
                'success' => true,
                'settings' => $settings
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Update settings
     */
    public function update(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'site_name' => 'required|string|max:255',
            'site_email' => 'required|email',
            'site_phone' => 'required|string',
            'site_address' => 'required|string',
            'currency' => 'required|in:CHF,EUR',
            'tax_rate' => 'required|numeric|min:0|max:100',
            'invoice_prefix' => 'required|string|max:10',
            'enable_2fa' => 'boolean',
            'enable_newsletter' => 'boolean',
            'maintenance_mode' => 'boolean',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            return response()->json([
                'success' => true,
                'message' => 'Paramètres mis à jour avec succès',
                'settings' => $request->all()
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get statistics for dashboard
     */
    public function getStats()
    {
        try {
            $stats = [
                'total_users' => \App\Models\User::count(),
                'total_members' => \App\Models\User::where('role', 'member')->count(),
                'total_subscriptions' => \App\Models\Subscription::count(),
                'active_subscriptions' => \App\Models\Subscription::where('status', 'active')->count(),
                'total_invoices' => \App\Models\Invoice::count(),
                'paid_invoices' => \App\Models\Invoice::where('status', 'paid')->count(),
                'pending_invoices' => \App\Models\Invoice::where('status', 'pending')->count(),
                'total_revenue' => \App\Models\Invoice::where('status', 'paid')->sum('total_amount'),
                'total_plans' => \App\Models\SubscriptionPlan::count(),
                
                // Membres récents (derniers 5)
                'recent_members' => \App\Models\User::where('role', 'member')
                    ->with('organization')
                    ->orderBy('created_at', 'desc')
                    ->limit(5)
                    ->get(),
                    
                // Activité récente (dernières 10 actions)
                'recent_activity' => \App\Models\Invoice::with(['organization'])
                    ->orderBy('created_at', 'desc')
                    ->limit(10)
                    ->get(),
            ];

            return response()->json([
                'success' => true,
                'stats' => $stats
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur: ' . $e->getMessage()
            ], 500);
        }
    }
}