<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\User;
use App\Models\Subscription;
use Illuminate\Support\Facades\DB;

class AdminController extends Controller
{
    /**
     * Get all members with filters and search
     */
    public function getMembers(Request $request)
    {
        try {
            $query = User::with(['organization', 'organization.subscriptions.subscriptionPlan'])
                ->where('role', 'member');

            // Search
            if ($request->has('search')) {
                $search = $request->search;
                $query->where(function($q) use ($search) {
                    $q->where('first_name', 'like', "%{$search}%")
                      ->orWhere('last_name', 'like', "%{$search}%")
                      ->orWhere('email', 'like', "%{$search}%")
                      ->orWhereHas('organization', function($q) use ($search) {
                          $q->where('name', 'like', "%{$search}%");
                      });
                });
            }

            // Filter by status
            if ($request->has('status')) {
                $query->whereHas('organization.subscriptions', function($q) use ($request) {
                    $q->where('status', $request->status);
                });
            }

            // Filter by plan
            if ($request->has('plan_id')) {
                $query->whereHas('organization.subscriptions', function($q) use ($request) {
                    $q->where('subscription_plan_id', $request->plan_id);
                });
            }

            // Sort
            $sortBy = $request->get('sort_by', 'created_at');
            $sortOrder = $request->get('sort_order', 'desc');
            $query->orderBy($sortBy, $sortOrder);

            // Pagination
            $perPage = $request->get('per_page', 15);
            $members = $query->paginate($perPage);

            return response()->json([
                'success' => true,
                'members' => $members
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Export members to CSV
     */
    public function exportMembers(Request $request)
    {
        try {
            $members = User::with(['organization', 'organization.subscriptions.subscriptionPlan'])
                ->where('role', 'member')
                ->get();

            $filename = 'membres_' . now()->format('Y-m-d_His') . '.csv';
            
            $headers = [
                'Content-Type' => 'text/csv',
                'Content-Disposition' => "attachment; filename=\"{$filename}\"",
            ];

            $callback = function() use ($members) {
                $file = fopen('php://output', 'w');
                
                // Header
                fputcsv($file, [
                    'ID',
                    'Prénom',
                    'Nom',
                    'Email',
                    'Téléphone',
                    'Organisation',
                    'Abonnement',
                    'Statut',
                    'Date inscription'
                ]);

                // Data
                foreach ($members as $member) {
                    $subscription = $member->organization->subscriptions->first();
                    
                    fputcsv($file, [
                        $member->id,
                        $member->first_name,
                        $member->last_name,
                        $member->email,
                        $member->phone,
                        $member->organization->name,
                        $subscription ? $subscription->subscriptionPlan->name : 'N/A',
                        $subscription ? $subscription->status : 'N/A',
                        $member->created_at->format('d/m/Y H:i')
                    ]);
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

    /**
     * Get member stats
     */
    public function getMemberStats()
    {
        try {
            $stats = [
                'total' => User::where('role', 'member')->count(),
                'active' => User::where('role', 'member')
                    ->whereHas('organization.subscriptions', function($q) {
                        $q->where('status', 'active');
                    })->count(),
                'pending' => User::where('role', 'member')
                    ->whereHas('organization.subscriptions', function($q) {
                        $q->where('status', 'pending');
                    })->count(),
                'new_this_month' => User::where('role', 'member')
                    ->whereMonth('created_at', now()->month)
                    ->count(),
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