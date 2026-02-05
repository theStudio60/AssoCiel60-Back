<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use App\Models\SubscriptionPlan;
use App\Models\Organization;

class DatatransController extends Controller
{
    public function createTransaction(Request $request)
    {
        try {
            // Valider les données
            $request->validate([
                'subscription_plan_id' => 'required|exists:subscription_plans,id',
                'organization_id' => 'required|exists:organizations,id',
            ]);

            // Récupérer le plan et l'organisation
            $plan = SubscriptionPlan::findOrFail($request->subscription_plan_id);
            $organization = Organization::findOrFail($request->organization_id);

            $merchantId = env('DATATRANS_MERCHANT_ID');
            $password = env('DATATRANS_PASSWORD');
            $apiUrl = env('DATATRANS_API_URL');
            $payUrl = env('DATATRANS_PAY_URL', 'https://pay.sandbox.datatrans.com');
            
            Log::info('=== DATATRANS REQUEST ===');
            Log::info('Merchant ID: ' . $merchantId);
            Log::info('API URL: ' . $apiUrl);
            Log::info('Plan: ' . $plan->name);
            Log::info('Organization: ' . $organization->name);

            $amount = (int)($plan->price_chf * 100); // Montant en centimes

            $response = Http::withBasicAuth($merchantId, $password)
                ->withHeaders(['Content-Type' => 'application/json'])
                ->post($apiUrl . '/v1/transactions', [
                    'currency' => 'CHF',
                    'refno' => 'DT-' . $organization->id . '-' . time(),
                    'amount' => $amount,
                    'redirect' => [
                        'successUrl' => env('FRONTEND_URL') . '/payment/success?plan_id=' . $plan->id . '&org_id=' . $organization->id,
                        'cancelUrl' => env('FRONTEND_URL') . '/payment/cancel',
                        'errorUrl' => env('FRONTEND_URL') . '/payment/error',
                    ],
                ]);

            Log::info('=== DATATRANS RESPONSE ===');
            Log::info('Status: ' . $response->status());
            Log::info('Body: ' . $response->body());

            if (!$response->successful()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Erreur API Datatrans',
                    'error' => $response->json(),
                    'status' => $response->status(),
                ], 500);
            }

            $data = $response->json();

            // Vérifier si transactionId existe
            if (!isset($data['transactionId'])) {
                return response()->json([
                    'success' => false,
                    'message' => 'TransactionId manquant dans la réponse',
                    'response' => $data,
                ], 500);
            }

            return response()->json([
                'success' => true,
                'redirect_url' => $payUrl . '/v1/start/' . $data['transactionId'],
                'transaction_id' => $data['transactionId'],
            ]);

        } catch (\Exception $e) {
            Log::error('=== DATATRANS ERROR ===');
            Log::error($e->getMessage());
            
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 500);
        }
    }
}