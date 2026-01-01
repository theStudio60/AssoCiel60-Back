<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Organization;
use App\Models\User;
use App\Models\SubscriptionPlan;
use App\Models\Subscription;
use App\Models\Invoice;
use App\Models\EmailSetting;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;
use App\Mail\WelcomeMail;
use App\Mail\SubscriptionConfirmedMail;
use Illuminate\Support\Facades\Mail;

class MembershipController extends Controller
{
    /**
     * Get all subscription plans
     */
    public function getPlans()
    {
        try {
            $plans = SubscriptionPlan::where('is_active', true)->get();
            
            return response()->json([
                'success' => true,
                'plans' => $plans
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la récupération des packs'
            ], 500);
        }
    }

    /**
     * Register new member with subscription
     */
    public function register(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'type' => 'required|in:individual,organization',
            'plan_id' => 'required|exists:subscription_plans,id',
            'country' => 'required|in:CHF,EUR',
            'first_name' => 'required|string|max:255',
            'last_name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email',
            'password' => 'required|string|min:8|confirmed',
            'phone' => 'required|string|max:20',
            'address' => 'required|string|max:255',
            'address_complement' => 'nullable|string|max:255',
            'postal_code' => 'required|string|max:10',
            'city' => 'required|string|max:255',
            'organization_name' => 'required_if:type,organization|nullable|string|max:255',
            'newsletter' => 'required|in:immediate,biweekly',
            'accept_terms' => 'required|accepted',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        DB::beginTransaction();

        try {
            // 1. Créer l'organisation
            $organization = Organization::create([
                'name' => $request->type === 'organization' 
                    ? $request->organization_name 
                    : $request->first_name . ' ' . $request->last_name,
                'email' => $request->email,
                'type' => $request->type,
                'address' => $request->address,
                'address_complement' => $request->address_complement,
                'zip_code' => $request->postal_code,
                'city' => $request->city,
                'country' => $request->country === 'CHF' ? 'CH' : 'FR',
                'phone' => $request->phone,
            ]);

            // 2. Créer l'utilisateur
            $user = User::create([
                'organization_id' => $organization->id,
                'first_name' => $request->first_name,
                'last_name' => $request->last_name,
                'email' => $request->email,
                'password' => Hash::make($request->password),
                'phone' => $request->phone,
                'role' => 'member',
                'newsletter_frequency' => $request->newsletter,
                'two_factor_enabled' => false,
            ]);

            // 3. Récupérer le plan
            $plan = SubscriptionPlan::findOrFail($request->plan_id);

            // 4. Créer l'abonnement
            $subscription = Subscription::create([
                'organization_id' => $organization->id,
                'subscription_plan_id' => $plan->id,
                'start_date' => now(),
                'end_date' => now()->addYear(),
                'status' => 'active',
                'auto_renew' => true,
            ]);

            // 5. Email de bienvenue - Vérifier si activé
            $welcomeEnabled = EmailSetting::get('welcome_enabled', true);
            if (filter_var($welcomeEnabled, FILTER_VALIDATE_BOOLEAN)) {
                try {
                    Mail::to($user->email)->send(new WelcomeMail($user, $organization));
                    \Log::info('Email de bienvenue envoyé à: ' . $user->email);
                } catch (\Exception $e) {
                    \Log::error('Email welcome error: ' . $e->getMessage());
                }
            } else {
                \Log::info('Email de bienvenue désactivé - non envoyé');
            }

            // 6. Email confirmation abonnement - Vérifier si activé
            $subscriptionEnabled = EmailSetting::get('subscription_enabled', true);
            if (filter_var($subscriptionEnabled, FILTER_VALIDATE_BOOLEAN)) {
                try {
                    Mail::to($user->email)->send(new SubscriptionConfirmedMail(
                        $subscription->load('subscriptionPlan'),
                        $user,
                        $organization
                    ));
                    \Log::info('Email confirmation abonnement envoyé à: ' . $user->email);
                } catch (\Exception $e) {
                    \Log::error('Email subscription confirmed error: ' . $e->getMessage());
                }
            } else {
                \Log::info('Email confirmation abonnement désactivé - non envoyé');
            }

            // 7. Calculer le montant selon le pays
            $amount = $request->country === 'CHF' 
                ? $plan->price_chf 
                : $plan->price_eur;

            // 8. Créer la facture
            $invoice = Invoice::create([
                'organization_id' => $organization->id,
                'subscription_id' => $subscription->id,
                'invoice_number' => 'INV-' . now()->format('Ymd') . '-' . str_pad($organization->id, 5, '0', STR_PAD_LEFT),
                'issue_date' => now(),
                'due_date' => now()->addDays(30),
                'amount' => $amount,
                'tax_amount' => 0,
                'total_amount' => $amount,
                'currency' => $request->country,
                'status' => 'pending',
            ]);

            DB::commit();

            // 9. Générer token pour connexion automatique
            $token = $user->createToken('auth_token')->plainTextToken;

            return response()->json([
                'success' => true,
                'message' => 'Inscription réussie',
                'user' => $user->load('organization'),
                'subscription' => $subscription->load('subscriptionPlan'),
                'invoice' => $invoice,
                'token' => $token,
                'payment_required' => true,
            ], 201);

        } catch (\Exception $e) {
            DB::rollBack();
            
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de l\'inscription: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Confirm payment and activate subscription
     */
    public function confirmPayment(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'invoice_id' => 'required|exists:invoices,id',
            'payment_method' => 'required|string',
            'transaction_id' => 'required|string',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        DB::beginTransaction();

        try {
            $invoice = Invoice::findOrFail($request->invoice_id);
            $subscription = $invoice->subscription;

            // Créer le paiement
            $payment = \App\Models\Payment::create([
                'subscription_id' => $subscription->id,
                'amount' => $invoice->amount,
                'currency' => $invoice->currency,
                'payment_method' => $request->payment_method,
                'transaction_id' => $request->transaction_id,
                'status' => 'completed',
                'payment_date' => now(),
            ]);

            // Mettre à jour la facture
            $invoice->update([
                'status' => 'paid',
                'paid_at' => now(),
            ]);

            // Activer l'abonnement
            $subscription->update([
                'status' => 'active',
            ]);

            DB::commit();

            // Email confirmation paiement - Vérifier si activé
            $paymentEnabled = EmailSetting::get('payment_enabled', true);
            if (filter_var($paymentEnabled, FILTER_VALIDATE_BOOLEAN)) {
                $user = \App\Models\User::where('organization_id', $invoice->organization_id)->first();
                
                if ($user) {
                    try {
                        Mail::to($user->email)->send(new \App\Mail\InvoicePaidMail(
                            $invoice->load('organization', 'subscription.subscriptionPlan'),
                            $user,
                            $invoice->organization
                        ));
                        \Log::info('Email confirmation paiement envoyé à: ' . $user->email);
                    } catch (\Exception $e) {
                        \Log::error('Email payment confirmation error: ' . $e->getMessage());
                    }
                } else {
                    \Log::info('Email confirmation paiement désactivé - non envoyé');
                }
            }

            return response()->json([
                'success' => true,
                'message' => 'Paiement confirmé',
                'payment' => $payment,
                'invoice' => $invoice,
                'subscription' => $subscription,
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la confirmation: ' . $e->getMessage()
            ], 500);
        }
    }
}