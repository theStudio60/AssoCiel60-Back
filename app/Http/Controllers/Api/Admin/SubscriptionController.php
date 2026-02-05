<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Subscription;
use App\Models\SubscriptionPlan;
use Illuminate\Support\Facades\Validator;

class SubscriptionController extends Controller
{
    /**
     * Get all subscriptions with filters
     */
    public function index(Request $request)
    {
        try {
            $query = Subscription::with(['organization', 'subscriptionPlan']);

            // Search
            if ($request->has('search') && $request->search) {
                $search = $request->search;
                $query->whereHas('organization', function($q) use ($search) {
                    $q->where('name', 'like', "%{$search}%");
                });
            }

            // Filter by status
            if ($request->has('status') && $request->status) {
                $query->where('status', $request->status);
            }

            // Filter by plan
            if ($request->has('plan_id') && $request->plan_id) {
                $query->where('subscription_plan_id', $request->plan_id);
            }

            // Sort
            $sortBy = $request->get('sort_by', 'created_at');
            $sortOrder = $request->get('sort_order', 'desc');
            $query->orderBy($sortBy, $sortOrder);

            // Pagination
            $perPage = $request->get('per_page', 10);
            $subscriptions = $query->paginate($perPage);

            return response()->json([
                'success' => true,
                'subscriptions' => $subscriptions
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get subscription stats
     */
    public function stats()
    {
        try {
            $total = Subscription::count();
            $active = Subscription::where('status', 'active')->count();
            $pending = Subscription::where('status', 'pending')->count();
            $expired = Subscription::where('status', 'expired')->count();

            $stats = [
                'total' => $total,
                'active' => $active,
                'pending' => $pending,
                'expired' => $expired,
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

    /**
     * Get single subscription
     */
    public function show($id)
    {
        try {
            $subscription = Subscription::with(['organization', 'subscriptionPlan', 'invoices'])
                ->findOrFail($id);

            return response()->json([
                'success' => true,
                'subscription' => $subscription
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Abonnement non trouvé'
            ], 404);
        }
    }

    /**
     * Update subscription status
     */
    public function updateStatus(Request $request, $id)
    {
        $validator = Validator::make($request->all(), [
            'status' => 'required|in:active,pending,expired,canceled',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $subscription = Subscription::findOrFail($id);
            $subscription->update(['status' => $request->status]);

            return response()->json([
                'success' => true,
                'message' => 'Statut mis à jour',
                'subscription' => $subscription->load(['organization', 'subscriptionPlan'])
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Renew subscription
     */
    public function renew($id)
    {
        try {
            $subscription = Subscription::findOrFail($id);
            
            $subscription->update([
                'start_date' => now(),
                'end_date' => now()->addYear(),
                'status' => 'active',
                'auto_renew' => true,
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Abonnement renouvelé',
                'subscription' => $subscription->load(['organization', 'subscriptionPlan'])
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Cancel subscription
     */
    public function cancel($id)
    {
        try {
            $subscription = Subscription::findOrFail($id);
            
            $subscription->update([
                'status' => 'canceled',
                'auto_renew' => false,
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Abonnement annulé',
                'subscription' => $subscription->load(['organization', 'subscriptionPlan'])
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Export subscriptions to CSV
     */
    public function export(Request $request)
    {
        try {
            $subscriptions = Subscription::with(['organization', 'subscriptionPlan'])->get();

            $filename = 'abonnements_' . now()->format('Y-m-d_His') . '.csv';
            
            $headers = [
                'Content-Type' => 'text/csv; charset=UTF-8',
                'Content-Disposition' => "attachment; filename=\"{$filename}\"",
            ];

            $callback = function() use ($subscriptions) {
                $file = fopen('php://output', 'w');
                
                // UTF-8 BOM
                fprintf($file, chr(0xEF).chr(0xBB).chr(0xBF));
                
                // Header
                fputcsv($file, [
                    'ID',
                    'Organisation',
                    'Plan',
                    'Date début',
                    'Date fin',
                    'Statut',
                    'Auto-renouvellement',
                    'Date création'
                ], ';');

                // Data
                foreach ($subscriptions as $sub) {
                    fputcsv($file, [
                        $sub->id,
                        $sub->organization->name,
                        $sub->subscriptionPlan->name,
                        $sub->start_date->format('d/m/Y'),
                        $sub->end_date->format('d/m/Y'),
                        $sub->status,
                        $sub->auto_renew ? 'Oui' : 'Non',
                        $sub->created_at->format('d/m/Y H:i')
                    ], ';');
                }

                fclose($file);
            };

            return response()->stream($callback, 200, $headers);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur export: ' . $e->getMessage()
            ], 500);
        }
    }
}