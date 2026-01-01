<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\SubscriptionPlan;
use Illuminate\Support\Facades\Validator;

class PlanController extends Controller
{
    /**
     * Get all plans with filters
     */
    public function index(Request $request)
    {
        try {
            $query = SubscriptionPlan::withCount('subscriptions');

            // Search
            if ($request->has('search') && $request->search) {
                $search = $request->search;
                $query->where(function($q) use ($search) {
                    $q->where('name', 'like', "%{$search}%")
                    ->orWhere('description', 'like', "%{$search}%");
                });
            }

            // Filter by status
            if ($request->has('is_active') && $request->is_active !== '') {
                $query->where('is_active', $request->is_active);
            }

            // Sort
            $sortBy = $request->get('sort_by', 'created_at');
            $sortOrder = $request->get('sort_order', 'desc');
            $query->orderBy($sortBy, $sortOrder);

            // Pagination
            $perPage = $request->get('per_page', 15);
            $plans = $query->paginate($perPage);

            return response()->json([
                'success' => true,
                'plans' => $plans
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get plan stats
     */
    public function stats()
    {
        try {
            $total = SubscriptionPlan::count();
            $active = SubscriptionPlan::where('is_active', 1)->count();
            $inactive = SubscriptionPlan::where('is_active', 0)->count();
            $subscriptionsCount = SubscriptionPlan::withCount('subscriptions')->get()->sum('subscriptions_count');

            $stats = [
                'total' => $total,
                'active' => $active,
                'inactive' => $inactive,
                'subscriptions_count' => $subscriptionsCount,
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
     * Get single plan
     */
    public function show($id)
    {
        try {
            $plan = SubscriptionPlan::withCount('subscriptions')->findOrFail($id);

            return response()->json([
                'success' => true,
                'plan' => $plan
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Pack non trouvé'
            ], 404);
        }
    }

    /**
     * Create new plan
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'price_chf' => 'required|numeric|min:0',
            'price_eur' => 'required|numeric|min:0',
            'duration_months' => 'required|integer|min:1',
            'is_active' => 'boolean',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $plan = SubscriptionPlan::create([
                'name' => $request->name,
                'description' => $request->description,
                'price_chf' => $request->price_chf,
                'price_eur' => $request->price_eur,
                'duration_months' => $request->duration_months,
                'is_active' => $request->is_active ?? 1,
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Pack créé avec succès',
                'plan' => $plan
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Update plan
     */
    public function update(Request $request, $id)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'price_chf' => 'required|numeric|min:0',
            'price_eur' => 'required|numeric|min:0',
            'duration_months' => 'required|integer|min:1',
            'is_active' => 'boolean',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $plan = SubscriptionPlan::findOrFail($id);
            
            $plan->update([
                'name' => $request->name,
                'description' => $request->description,
                'price_chf' => $request->price_chf,
                'price_eur' => $request->price_eur,
                'duration_months' => $request->duration_months,
                'is_active' => $request->is_active ?? $plan->is_active,
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Pack mis à jour',
                'plan' => $plan
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Delete plan
     */
    public function destroy($id)
    {
        try {
            $plan = SubscriptionPlan::withCount('subscriptions')->findOrFail($id);
            
            // Empêcher la suppression si le plan a des abonnements actifs
            if ($plan->subscriptions_count > 0) {
                return response()->json([
                    'success' => false,
                    'message' => 'Impossible de supprimer un pack avec des abonnements actifs'
                ], 422);
            }

            $plan->delete();

            return response()->json([
                'success' => true,
                'message' => 'Pack supprimé'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Toggle plan active status
     */
    public function toggleStatus($id)
    {
        try {
            $plan = SubscriptionPlan::findOrFail($id);
            $plan->is_active = !$plan->is_active;
            $plan->save();

            return response()->json([
                'success' => true,
                'message' => $plan->is_active ? 'Pack activé' : 'Pack désactivé',
                'plan' => $plan
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Export plans to CSV
     */
    public function export(Request $request)
    {
        try {
            $plans = SubscriptionPlan::withCount('subscriptions')->get();

            $filename = 'packs_' . now()->format('Y-m-d_His') . '.csv';
            
            $headers = [
                'Content-Type' => 'text/csv; charset=UTF-8',
                'Content-Disposition' => "attachment; filename=\"{$filename}\"",
            ];

            $callback = function() use ($plans) {
                $file = fopen('php://output', 'w');
                
                // UTF-8 BOM
                fprintf($file, chr(0xEF).chr(0xBB).chr(0xBF));
                
                // Header
                fputcsv($file, [
                    'ID',
                    'Nom',
                    'Description',
                    'Prix CHF',
                    'Prix EUR',
                    'Durée (mois)',
                    'Actif',
                    'Nombre abonnements',
                    'Date création'
                ], ';');

                // Data
                foreach ($plans as $plan) {
                    fputcsv($file, [
                        $plan->id,
                        $plan->name,
                        $plan->description,
                        $plan->price_chf,
                        $plan->price_eur,
                        $plan->duration_months,
                        $plan->is_active ? 'Oui' : 'Non',
                        $plan->subscriptions_count,
                        $plan->created_at->format('d/m/Y H:i')
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