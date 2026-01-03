<?php

namespace App\Http\Controllers\Api\Member;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Subscription;
use App\Models\Invoice;
use App\Models\User;
use App\Models\ActivityLog;

class MemberDashboardController extends Controller
{
    /**
     * Get member subscription details
     */
    public function getSubscription(Request $request)
    {
        try {
            $user = $request->user();
            
            $subscription = Subscription::with(['subscriptionPlan', 'organization'])
                ->where('organization_id', $user->organization_id)
                ->orderBy('created_at', 'desc')
                ->first();

            if (!$subscription) {
                return response()->json([
                    'success' => false,
                    'message' => 'Aucun abonnement trouvé'
                ], 404);
            }

            // ✅ CORRECTION - Calcul correct des jours restants
            $endDate = \Carbon\Carbon::parse($subscription->end_date);
            $now = \Carbon\Carbon::now();
            
            // Si la date de fin est passée, jours restants = 0
            if ($endDate->isPast()) {
                $daysRemaining = 0;
            } else {
                // Sinon, calculer la différence en jours
                $daysRemaining = (int) $now->diffInDays($endDate, false);
            }
            
            return response()->json([
                'success' => true,
                'subscription' => $subscription,
                'days_remaining' => max(0, $daysRemaining), // Toujours >= 0
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get member invoices
     */
    public function getInvoices(Request $request)
    {
        try {
            $user = $request->user();
            
            $query = Invoice::with(['subscription.subscriptionPlan'])
                ->where('organization_id', $user->organization_id)
                ->orderBy('created_at', 'desc');

            // Filter by status
            if ($request->has('status') && $request->status) {
                $query->where('status', $request->status);
            }

            // Search
            if ($request->has('search') && $request->search) {
                $query->where('invoice_number', 'like', '%' . $request->search . '%');
            }

            $perPage = $request->get('per_page', 10);
            $invoices = $query->paginate($perPage);

            // Stats
            $stats = [
                'total' => Invoice::where('organization_id', $user->organization_id)->count(),
                'paid' => Invoice::where('organization_id', $user->organization_id)->where('status', 'paid')->count(),
                'pending' => Invoice::where('organization_id', $user->organization_id)->where('status', 'pending')->count(),
                'total_amount' => Invoice::where('organization_id', $user->organization_id)->where('status', 'paid')->sum('total_amount'),
            ];

            return response()->json([
                'success' => true,
                'invoices' => $invoices,
                'stats' => $stats,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Download invoice PDF
     */
    public function downloadInvoice(Request $request, $id)
    {
        try {
            $user = $request->user();
            
            $invoice = Invoice::with(['organization', 'subscription.subscriptionPlan'])
                ->where('organization_id', $user->organization_id)
                ->findOrFail($id);

            $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadView('invoices.pdf', ['invoice' => $invoice])
                ->setPaper('a4', 'portrait');

            return $pdf->download('Facture_' . $invoice->invoice_number . '.pdf');
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Request subscription renewal
     */
    public function requestRenewal(Request $request)
    {
        try {
            $user = $request->user();
            
            $subscription = Subscription::where('organization_id', $user->organization_id)
                ->orderBy('created_at', 'desc')
                ->first();

            if (!$subscription) {
                return response()->json([
                    'success' => false,
                    'message' => 'Aucun abonnement trouvé'
                ], 404);
            }

            // TODO: Logique de renouvellement ou notification admin
            
            return response()->json([
                'success' => true,
                'message' => 'Demande de renouvellement envoyée'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Update member settings
     */
    public function updateSettings(Request $request)
    {
        try {
            $user = $request->user();
            
            $validated = $request->validate([
                'newsletter_frequency' => 'nullable|in:immediate,biweekly',
                'notifications_enabled' => 'boolean',
                'language' => 'nullable|in:fr,en',
            ]);

            $user->update($validated);

            return response()->json([
                'success' => true,
                'message' => 'Paramètres mis à jour',
                'user' => $user,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Toggle auto-renew
     */
    public function toggleAutoRenew(Request $request)
    {
        try {
            $user = $request->user();
            
            $subscription = Subscription::where('organization_id', $user->organization_id)
                ->orderBy('created_at', 'desc')
                ->first();

            if (!$subscription) {
                return response()->json([
                    'success' => false,
                    'message' => 'Aucun abonnement trouvé'
                ], 404);
            }

            $subscription->update([
                'auto_renew' => !$subscription->auto_renew
            ]);

            ActivityLog::log(
                'updated',
                "Renouvellement automatique " . ($subscription->auto_renew ? 'activé' : 'désactivé') . " par {$user->first_name} {$user->last_name}",
                Subscription::class,
                $subscription->id
            );

            return response()->json([
                'success' => true,
                'message' => 'Paramètre mis à jour',
                'auto_renew' => $subscription->auto_renew,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur: ' . $e->getMessage()
            ], 500);
        }
    }
}