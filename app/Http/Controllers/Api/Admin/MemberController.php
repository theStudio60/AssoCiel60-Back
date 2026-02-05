<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\User;
use App\Models\Subscription;
use Illuminate\Support\Facades\DB;
use App\Models\ActivityLog;
use App\Models\Organization;

class MemberController extends Controller
{
    /**
     * Get all members with filters and search
     */
    public function index(Request $request)
    {
        try {
            $query = User::with(['organization', 'organization.subscriptions.subscriptionPlan'])
                ->where('role', 'member');

            // Search
            if ($request->has('search') && $request->search) {
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
            if ($request->has('status') && $request->status) {
                $query->whereHas('organization.subscriptions', function($q) use ($request) {
                    $q->where('status', $request->status);
                });
            }

            // Filter by plan
            if ($request->has('plan_id') && $request->plan_id) {
                $query->whereHas('organization.subscriptions', function($q) use ($request) {
                    $q->where('subscription_plan_id', $request->plan_id);
                });
            }

            // Sort
            $sortBy = $request->get('sort_by', 'created_at');
            $sortOrder = $request->get('sort_order', 'desc');
            $query->orderBy($sortBy, $sortOrder);

            // Pagination
            $perPage = $request->get('per_page', 10);
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
     * Get member stats
     */
    public function stats()
    {
        try {
            $total = User::where('role', 'member')->count();
            
            $active = User::where('role', 'member')
                ->whereHas('organization.subscriptions', function($q) {
                    $q->where('status', 'active');
                })->count();
            
            $pending = User::where('role', 'member')
                ->whereHas('organization.subscriptions', function($q) {
                    $q->where('status', 'pending');
                })->count();
            
            $newThisMonth = User::where('role', 'member')
                ->whereMonth('created_at', now()->month)
                ->whereYear('created_at', now()->year)
                ->count();

            $stats = [
                'total' => $total,
                'active' => $active,
                'pending' => $pending,
                'new_this_month' => $newThisMonth,
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
     * Export members to CSV
     */
    public function export(Request $request)
    {
        try {
            $members = User::with(['organization', 'organization.subscriptions.subscriptionPlan'])
                ->where('role', 'member')
                ->get();

            $filename = 'membres_' . now()->format('Y-m-d_His') . '.csv';
            
            $headers = [
                'Content-Type' => 'text/csv; charset=UTF-8',
                'Content-Disposition' => "attachment; filename=\"{$filename}\"",
            ];

            $callback = function() use ($members) {
                $file = fopen('php://output', 'w');
                
                // UTF-8 BOM pour Excel
                fprintf($file, chr(0xEF).chr(0xBB).chr(0xBF));
                
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
                ], ';');

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
                        $subscription ? $subscription->status : 'inactive',
                        $member->created_at->format('d/m/Y H:i')
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

    /**
     * Get single member details
     */
    public function show($id)
    {
        try {
            $member = User::with(['organization', 'organization.subscriptions.subscriptionPlan'])
                ->where('role', 'member')
                ->findOrFail($id);

            return response()->json([
                'success' => true,
                'member' => $member
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Membre non trouvé'
            ], 404);
        }
    }

    /**
     * Update member
     */
    public function update(Request $request, $id)
    {
        try {
            $member = User::where('role', 'member')->findOrFail($id);
            
            $member->update($request->only(['first_name', 'last_name', 'email', 'phone']));

            return response()->json([
                'success' => true,
                'message' => 'Membre mis à jour',
                'member' => $member->load('organization')
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Delete member
     */
    public function destroy($id)
    {
        try {
            $member = User::where('role', 'member')->findOrFail($id);
            $member->delete();

            return response()->json([
                'success' => true,
                'message' => 'Membre supprimé'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Toggle member status (active/inactive)
     */
    public function toggleStatus(Request $request, $id)
    {
        try {
            $user = User::findOrFail($id);
            
            // Récupérer l'organisation et l'abonnement
            $organization = $user->organization;
            
            if (!$organization) {
                return response()->json([
                    'success' => false,
                    'message' => 'Aucune organisation trouvée pour ce membre'
                ], 404);
            }
            
            $subscription = $organization->subscriptions()->latest()->first();
            
            if (!$subscription) {
                return response()->json([
                    'success' => false,
                    'message' => 'Aucun abonnement trouvé'
                ], 404);
            }
            
            // Toggle status (active <-> expired)
            $newStatus = ($subscription->status === 'active') ? 'expired' : 'active';
            $subscription->status = $newStatus;
            $subscription->save();
            
            // Log
            ActivityLog::log(
                'subscription_status_changed',
                "Statut de l'abonnement de {$user->first_name} {$user->last_name} changé en {$newStatus}",
                \App\Models\Subscription::class,
                $subscription->id
            );
            
            return response()->json([
                'success' => true,
                'message' => 'Statut modifié avec succès',
                'status' => $newStatus
            ]);
        } catch (\Exception $e) {
            \Log::error('Toggle status error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Erreur: ' . $e->getMessage()
            ], 500);
        }
    }
}