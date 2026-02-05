<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Stripe\Stripe;
use Stripe\Checkout\Session;
use App\Models\SubscriptionPlan;
use App\Models\Organization;
use App\Models\Payment;
use App\Models\ActivityLog;
use Illuminate\Support\Facades\Log;

class StripeController extends Controller
{
    public function __construct()
    {
        Stripe::setApiKey(env('STRIPE_SECRET'));
    }

    /**
     * Créer une session Stripe Checkout
     */
    public function createCheckoutSession(Request $request)
    {
        try {
            $request->validate([
                'subscription_plan_id' => 'required|exists:subscription_plans,id',
                'organization_id' => 'required|exists:organizations,id',
                'currency' => 'nullable|in:CHF,EUR',
            ]);

            $plan = SubscriptionPlan::findOrFail($request->subscription_plan_id);
            $organization = Organization::findOrFail($request->organization_id);

            // Récupérer la devise (EUR ou CHF)
            $currency = $request->currency ?? 'CHF';
            $currency_lower = strtolower($currency);

            // Utiliser le bon prix selon la devise
            $amount = ($currency === 'EUR') ? $plan->price_eur : $plan->price_chf;

            // Créer la session Stripe Checkout
            $session = Session::create([
                'payment_method_types' => ['card'],
                'line_items' => [[
                    'price_data' => [
                        'currency' => $currency_lower,
                        'product_data' => [
                            'name' => $plan->name,
                            'description' => "Abonnement {$plan->name}",
                        ],
                        'unit_amount' => (int)($amount * 100), // en centimes
                    ],
                    'quantity' => 1,
                ]],
                'mode' => 'payment',
                'success_url' => env('FRONTEND_URL') . '/payment/success?session_id={CHECKOUT_SESSION_ID}&plan_id=' . $plan->id . '&org_id=' . $organization->id,
                'cancel_url' => env('FRONTEND_URL') . '/payment/cancel',
                'metadata' => [
                    'organization_id' => $organization->id,
                    'plan_id' => $plan->id,
                    'currency' => $currency,
                ],
            ]);

            // Log
            ActivityLog::log(
                'payment_initiated',
                "Paiement Stripe initié pour {$organization->name}",
                null, null,
                ['session_id' => $session->id, 'amount' => $amount, 'currency' => $currency]
            );

            return response()->json([
                'success' => true,
                'redirect_url' => $session->url,
            ]);

        } catch (\Exception $e) {
            Log::error('STRIPE ERROR: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Confirmer le paiement Stripe et créer l'abonnement
     */
    public function confirmPayment(Request $request)
    {
        try {
            $request->validate([
                'session_id' => 'required',
                'plan_id' => 'required|exists:subscription_plans,id',
                'organization_id' => 'required|exists:organizations,id',
            ]);

            // Récupérer la session Stripe
            $session = Session::retrieve($request->session_id);

            // Vérifier que le paiement est bien complété
            if ($session->payment_status !== 'paid') {
                return response()->json([
                    'success' => false,
                    'message' => 'Le paiement n\'est pas encore confirmé',
                ], 400);
            }

            $plan = SubscriptionPlan::findOrFail($request->plan_id);
            $organization = Organization::findOrFail($request->organization_id);

            // Récupérer la devise depuis les metadata de la session
            $currency = $session->metadata->currency ?? 'CHF';
            $currency_lower = strtolower($currency);
            
            // Récupérer le montant réel payé depuis Stripe
            $amount = $session->amount_total / 100; // Stripe retourne en centimes

            // D'ABORD : Créer ou mettre à jour l'abonnement
            $subscription = \App\Models\Subscription::updateOrCreate(
                ['organization_id' => $organization->id],
                [
                    'subscription_plan_id' => $plan->id,
                    'start_date' => now(),
                    'end_date' => now()->addYear(),
                    'status' => 'active',
                    'auto_renew' => true,
                ]
            );

            // ENSUITE : Mettre à jour ou créer le payment AVEC subscription_id
            $payment = Payment::where('transaction_id', $request->session_id)->first();
            if ($payment) {
                $payment->update([
                    'subscription_id' => $subscription->id,
                    'status' => 'completed',
                    'paid_at' => now(),
                    'amount' => $amount,
                    'currency' => strtoupper($currency),
                ]);
            } else {
                // Si pas trouvé, créer avec subscription_id
                $payment = Payment::create([
                    'organization_id' => $organization->id,
                    'subscription_id' => $subscription->id,
                    'payment_method' => 'stripe',
                    'transaction_id' => $request->session_id,
                    'amount' => $amount,
                    'currency' => strtoupper($currency),
                    'status' => 'completed',
                    'paid_at' => now(),
                ]);
            }

            // Créer la facture avec TOUS les champs requis
            $invoice = \App\Models\Invoice::create([
                'organization_id' => $organization->id,
                'subscription_id' => $subscription->id,
                'subscription_plan_id' => $plan->id,
                'invoice_number' => 'INV-' . now()->format('YmdHis') . '-' . strtoupper(substr(uniqid(), -4)),
                'issue_date' => now(),
                'due_date' => now()->addDays(30),
                'amount' => $amount,
                'tax_amount' => 0,
                'total_amount' => $amount,
                'currency' => strtoupper($currency),
                'status' => 'paid',
                'paid_at' => now(),
                'payment_method' => 'Stripe',
                'transaction_id' => $request->session_id,
            ]);

            // Log
            ActivityLog::log(
                'payment_completed',
                "Paiement Stripe confirmé pour {$organization->name}",
                Payment::class,
                $payment->id,
                ['session_id' => $request->session_id, 'amount' => $amount, 'currency' => $currency]
            );

            return response()->json([
                'success' => true,
                'message' => 'Paiement confirmé',
                'subscription' => $subscription,
                'invoice' => $invoice,
            ]);

        } catch (\Exception $e) {
            Log::error('STRIPE CONFIRM ERROR: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    public function webhook(Request $request)
    {
        // Webhook pour confirmation paiement (optionnel pour production)
        return response()->json(['success' => true]);
    }
}