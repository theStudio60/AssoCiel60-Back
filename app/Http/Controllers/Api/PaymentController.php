<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Subscription;
use App\Models\Invoice;
use App\Models\Organization;
use App\Models\SubscriptionPlan;
use App\Models\ActivityLog;
use PayPalCheckoutSdk\Core\PayPalHttpClient;
use PayPalCheckoutSdk\Core\SandboxEnvironment;
use PayPalCheckoutSdk\Core\ProductionEnvironment;
use PayPalCheckoutSdk\Orders\OrdersCreateRequest;
use PayPalCheckoutSdk\Orders\OrdersCaptureRequest;

class PaymentController extends Controller
{
    private $client;

    public function __construct()
    {
        $clientId = config('paypal.mode') === 'sandbox' 
            ? config('paypal.sandbox.client_id') 
            : config('paypal.live.client_id');
            
        $clientSecret = config('paypal.mode') === 'sandbox' 
            ? config('paypal.sandbox.client_secret') 
            : config('paypal.live.client_secret');

        $environment = config('paypal.mode') === 'sandbox'
            ? new SandboxEnvironment($clientId, $clientSecret)
            : new ProductionEnvironment($clientId, $clientSecret);

        $this->client = new PayPalHttpClient($environment);
    }

    /**
     * Créer le paiement PayPal
     */
    public function createPayment(Request $request)
    {
        $request->validate([
            'subscription_plan_id' => 'required|exists:subscription_plans,id',
            'organization_id' => 'required|exists:organizations,id',
        ]);

        try {
            $plan = SubscriptionPlan::findOrFail($request->subscription_plan_id);
            $organization = Organization::findOrFail($request->organization_id);

            $orderRequest = new OrdersCreateRequest();
            $orderRequest->prefer('return=representation');
            $orderRequest->body = [
                'intent' => 'CAPTURE',
                'purchase_units' => [[
                    'reference_id' => 'PLAN_' . $plan->id,
                    'description' => "Abonnement {$plan->name} - {$organization->name}",
                    'amount' => [
                        'currency_code' => 'CHF',
                        'value' => number_format((float)$plan->price_chf, 2, '.', ''),
                    ],
                ]],
                'application_context' => [
                    'brand_name' => 'ALPRAIL',
                    'locale' => 'fr-CH',
                    'landing_page' => 'BILLING',
                    'user_action' => 'PAY_NOW',
                    'return_url' => env('FRONTEND_URL') . '/payment/success?plan_id=' . $plan->id . '&org_id=' . $organization->id,
                    'cancel_url' => env('FRONTEND_URL') . '/payment/cancel',
                ],
            ];

            $response = $this->client->execute($orderRequest);

            // Trouver le lien d'approbation
            $approvalUrl = '';
            foreach ($response->result->links as $link) {
                if ($link->rel === 'approve') {
                    $approvalUrl = $link->href;
                    break;
                }
            }

            // Log
            ActivityLog::log(
                'payment_initiated',
                "Paiement PayPal initié pour {$organization->name} - {$plan->name}",
                SubscriptionPlan::class,
                $plan->id,
                [
                    'order_id' => $response->result->id,
                    'amount' => $plan->price_chf,
                    'currency' => 'CHF',
                    'organization_id' => $organization->id,
                ]
            );

            return response()->json([
                'success' => true,
                'approval_url' => $approvalUrl,
                'order_id' => $response->result->id,
            ]);

        } catch (\Exception $e) {
            \Log::error('PayPal payment creation error: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la création du paiement: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Capturer le paiement après approbation
     */
    public function executePayment(Request $request)
    {
        $request->validate([
            'order_id' => 'required',
            'plan_id' => 'required|exists:subscription_plans,id',
            'organization_id' => 'required|exists:organizations,id',
        ]);

        try {
            $captureRequest = new OrdersCaptureRequest($request->order_id);
            $captureRequest->prefer('return=representation');

            $response = $this->client->execute($captureRequest);

            if ($response->result->status === 'COMPLETED') {
                $plan = SubscriptionPlan::findOrFail($request->plan_id);
                $organization = Organization::findOrFail($request->organization_id);

                // Créer l'abonnement
                $subscription = Subscription::create([
                    'organization_id' => $organization->id,
                    'subscription_plan_id' => $plan->id,
                    'start_date' => now(),
                    'end_date' => now()->addYear(),
                    'status' => 'active',
                    'auto_renew' => false,
                ]);

                // Créer la facture
                $invoice = Invoice::create([
                    'organization_id' => $organization->id,
                    'subscription_id' => $subscription->id,
                    'invoice_number' => 'INV-' . now()->format('Ymd') . '-' . str_pad($organization->id, 5, '0', STR_PAD_LEFT),
                    'issue_date' => now(),
                    'due_date' => now()->addDays(30),
                    'amount' => $plan->price_chf,
                    'tax_amount' => 0,
                    'total_amount' => $plan->price_chf,
                    'currency' => 'CHF',
                    'status' => 'paid',
                    'paid_at' => now(),
                    'payment_method' => 'PayPal',
                    'transaction_id' => $request->order_id,
                ]);

                // Log
                ActivityLog::log(
                    'payment_completed',
                    "Paiement PayPal confirmé pour {$organization->name} - {$plan->name}",
                    Subscription::class,
                    $subscription->id,
                    [
                        'order_id' => $request->order_id,
                        'amount' => $plan->price_chf,
                        'invoice_id' => $invoice->id,
                    ]
                );

                // Envoyer email de confirmation
                $user = \App\Models\User::where('organization_id', $organization->id)->first();
                if ($user) {
                    $emailEnabled = \App\Models\EmailSetting::get('subscription_enabled', true);
                    if (filter_var($emailEnabled, FILTER_VALIDATE_BOOLEAN)) {
                        try {
                            \Mail::to($user->email)->send(
                                new \App\Mail\SubscriptionConfirmationMail($subscription->load('subscriptionPlan'), $user)
                            );
                        } catch (\Exception $e) {
                            \Log::error('Email error: ' . $e->getMessage());
                        }
                    }
                }

                return response()->json([
                    'success' => true,
                    'message' => 'Paiement réussi !',
                    'subscription' => $subscription,
                    'invoice' => $invoice,
                ]);
            }

            return response()->json([
                'success' => false,
                'message' => 'Le paiement n\'a pas été complété'
            ], 400);

        } catch (\Exception $e) {
            \Log::error('PayPal payment execution error: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de l\'exécution du paiement: ' . $e->getMessage()
            ], 500);
        }
    }
}