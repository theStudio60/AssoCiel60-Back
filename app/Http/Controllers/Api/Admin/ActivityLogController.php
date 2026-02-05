<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\ActivityLog;

class ActivityLogController extends Controller
{
    /**
     * Get activity logs with filters
     */
    public function index(Request $request)
    {
        try {
            $query = ActivityLog::with('user');

            // Filter by user
            if ($request->has('user_id') && $request->user_id) {
                $query->where('user_id', $request->user_id);
            }

            // Filter by action
            if ($request->has('action') && $request->action) {
                $query->where('action', $request->action);
            }

            // Filter by date range
            if ($request->has('date_from') && $request->date_from) {
                $query->whereDate('created_at', '>=', $request->date_from);
            }
            
            if ($request->has('date_to') && $request->date_to) {
                $query->whereDate('created_at', '<=', $request->date_to);
            }

            // Search in description
            if ($request->has('search') && $request->search) {
                $query->where('description', 'like', '%' . $request->search . '%');
            }

            // Sort by newest first
            $query->orderBy('created_at', 'desc');

            // Pagination
            $perPage = $request->get('per_page', 10);
            $logs = $query->paginate($perPage);

            return response()->json([
                'success' => true,
                'logs' => $logs
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get activity stats
     */
    public function stats()
    {
        try {
            $total = ActivityLog::count();
            $today = ActivityLog::whereDate('created_at', today())->count();
            $thisWeek = ActivityLog::whereBetween('created_at', [now()->startOfWeek(), now()->endOfWeek()])->count();
            $thisMonth = ActivityLog::whereMonth('created_at', now()->month)->count();

            // Actions les plus fréquentes
            $topActions = ActivityLog::selectRaw('action, COUNT(*) as count')
                ->groupBy('action')
                ->orderBy('count', 'desc')
                ->limit(5)
                ->get();

            // Utilisateurs les plus actifs
            $topUsers = ActivityLog::with('user')
                ->selectRaw('user_id, COUNT(*) as count')
                ->groupBy('user_id')
                ->orderBy('count', 'desc')
                ->limit(5)
                ->get();

            return response()->json([
                'success' => true,
                'stats' => [
                    'total' => $total,
                    'today' => $today,
                    'this_week' => $thisWeek,
                    'this_month' => $thisMonth,
                    'top_actions' => $topActions,
                    'top_users' => $topUsers,
                ]
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Export logs to CSV
     */
    public function export(Request $request)
    {
        try {
            $query = ActivityLog::with('user')->orderBy('created_at', 'desc');

            // Apply same filters as index
            if ($request->has('user_id') && $request->user_id) {
                $query->where('user_id', $request->user_id);
            }
            if ($request->has('action') && $request->action) {
                $query->where('action', $request->action);
            }
            if ($request->has('date_from') && $request->date_from) {
                $query->whereDate('created_at', '>=', $request->date_from);
            }
            if ($request->has('date_to') && $request->date_to) {
                $query->whereDate('created_at', '<=', $request->date_to);
            }

            $logs = $query->get();

            $filename = 'logs_activite_' . now()->format('Y-m-d_His') . '.csv';
            
            $headers = [
                'Content-Type' => 'text/csv; charset=UTF-8',
                'Content-Disposition' => "attachment; filename=\"{$filename}\"",
            ];

            $callback = function() use ($logs) {
                $file = fopen('php://output', 'w');
                
                // UTF-8 BOM
                fprintf($file, chr(0xEF).chr(0xBB).chr(0xBF));
                
                // Header
                fputcsv($file, [
                    'Date',
                    'Heure',
                    'Utilisateur',
                    'Action',
                    'Description',
                    'IP',
                ], ';');

                // Data
                foreach ($logs as $log) {
                    fputcsv($file, [
                        $log->created_at->format('d/m/Y'),
                        $log->created_at->format('H:i:s'),
                        $log->user ? $log->user->first_name . ' ' . $log->user->last_name : 'Système',
                        $log->action,
                        $log->description,
                        $log->ip_address,
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
     * Delete old logs (older than X days)
     */
    /**
 * Delete old logs (older than X days)
 */
public function cleanup(Request $request)
{
    try {
        $days = $request->get('days', 90);
        
        $deleted = ActivityLog::where('created_at', '<', now()->subDays($days))->delete();

        return response()->json([
            'success' => true,
            'message' => "$deleted logs supprimés",
            'deleted' => $deleted
        ]);
    } catch (\Exception $e) {
        return response()->json([
            'success' => false,
            'message' => 'Erreur: ' . $e->getMessage()
        ], 500);
    }
}
}