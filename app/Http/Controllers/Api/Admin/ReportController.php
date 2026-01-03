<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\User;
use App\Models\Subscription;
use App\Models\Invoice;
use App\Models\Organization;
use App\Models\ActivityLog;
use Carbon\Carbon;

class ReportController extends Controller
{
    /**
     * Get monthly report data
     */
    public function getMonthlyData(Request $request)
    {
        try {
            $month = $request->get('month', now()->format('Y-m'));
            $startDate = Carbon::parse($month . '-01')->startOfMonth();
            $endDate = Carbon::parse($month . '-01')->endOfMonth();

            // Members stats
            $totalMembers = User::where('role', 'member')->count();
            $newMembers = User::where('role', 'member')
                ->whereBetween('created_at', [$startDate, $endDate])
                ->count();

            // Subscriptions stats
            $activeSubscriptions = Subscription::where('status', 'active')->count();
            $newSubscriptions = Subscription::whereBetween('created_at', [$startDate, $endDate])->count();
            $expiredSubscriptions = Subscription::where('status', 'expired')
                ->whereBetween('updated_at', [$startDate, $endDate])
                ->count();

            // Revenue stats
            $totalRevenue = Invoice::where('status', 'paid')
                ->whereBetween('paid_at', [$startDate, $endDate])
                ->sum('total_amount');

            $pendingRevenue = Invoice::where('status', 'pending')
                ->whereBetween('created_at', [$startDate, $endDate])
                ->sum('total_amount');

            $invoicesCount = Invoice::whereBetween('created_at', [$startDate, $endDate])->count();
            $paidInvoices = Invoice::where('status', 'paid')
                ->whereBetween('paid_at', [$startDate, $endDate])
                ->count();

            // Top organizations
            $topOrganizations = Organization::withCount(['subscriptions', 'invoices'])
                ->orderBy('subscriptions_count', 'desc')
                ->limit(5)
                ->get();

            // Activity summary
            $activityCount = ActivityLog::whereBetween('created_at', [$startDate, $endDate])->count();
            $topActions = ActivityLog::selectRaw('action, COUNT(*) as count')
                ->whereBetween('created_at', [$startDate, $endDate])
                ->groupBy('action')
                ->orderBy('count', 'desc')
                ->limit(5)
                ->get();

            // Daily revenue chart data
            $dailyRevenue = Invoice::where('status', 'paid')
                ->whereBetween('paid_at', [$startDate, $endDate])
                ->selectRaw('DATE(paid_at) as date, SUM(total_amount) as total')
                ->groupBy('date')
                ->orderBy('date')
                ->get();

            // Subscription plans distribution
            $planDistribution = Subscription::join('subscription_plans', 'subscriptions.subscription_plan_id', '=', 'subscription_plans.id')
                ->selectRaw('subscription_plans.name, COUNT(*) as count')
                ->where('subscriptions.status', 'active')
                ->groupBy('subscription_plans.name')
                ->get();

            return response()->json([
                'success' => true,
                'month' => $month,
                'period' => [
                    'start' => $startDate->format('d/m/Y'),
                    'end' => $endDate->format('d/m/Y'),
                ],
                'stats' => [
                    'members' => [
                        'total' => $totalMembers,
                        'new' => $newMembers,
                        'growth' => $totalMembers > 0 ? round(($newMembers / $totalMembers) * 100, 2) : 0,
                    ],
                    'subscriptions' => [
                        'active' => $activeSubscriptions,
                        'new' => $newSubscriptions,
                        'expired' => $expiredSubscriptions,
                    ],
                    'revenue' => [
                        'total' => round($totalRevenue, 2),
                        'pending' => round($pendingRevenue, 2),
                        'invoices_count' => $invoicesCount,
                        'paid_invoices' => $paidInvoices,
                        'payment_rate' => $invoicesCount > 0 ? round(($paidInvoices / $invoicesCount) * 100, 2) : 0,
                    ],
                    'activity' => [
                        'total' => $activityCount,
                        'top_actions' => $topActions,
                    ],
                ],
                'charts' => [
                    'daily_revenue' => $dailyRevenue,
                    'plan_distribution' => $planDistribution,
                ],
                'top_organizations' => $topOrganizations,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Generate monthly PDF report
     */
    public function generateMonthlyPdf(Request $request)
    {
        try {
            $month = $request->get('month', now()->format('Y-m'));
            $startDate = Carbon::parse($month . '-01')->startOfMonth();
            $endDate = Carbon::parse($month . '-01')->endOfMonth();

            // Get all stats
            $totalMembers = User::where('role', 'member')->count();
            $newMembers = User::where('role', 'member')
                ->whereBetween('created_at', [$startDate, $endDate])
                ->count();

            $activeSubscriptions = Subscription::where('status', 'active')->count();
            $newSubscriptions = Subscription::whereBetween('created_at', [$startDate, $endDate])->count();
            $expiredSubscriptions = Subscription::where('status', 'expired')
                ->whereBetween('updated_at', [$startDate, $endDate])
                ->count();

            $totalRevenue = Invoice::where('status', 'paid')
                ->whereBetween('paid_at', [$startDate, $endDate])
                ->sum('total_amount');

            $pendingRevenue = Invoice::where('status', 'pending')
                ->whereBetween('created_at', [$startDate, $endDate])
                ->sum('total_amount');

            $invoicesCount = Invoice::whereBetween('created_at', [$startDate, $endDate])->count();
            $paidInvoices = Invoice::where('status', 'paid')
                ->whereBetween('paid_at', [$startDate, $endDate])
                ->count();

            $topOrganizations = Organization::withCount(['subscriptions', 'invoices'])
                ->orderBy('subscriptions_count', 'desc')
                ->limit(10)
                ->get();

            $activityCount = ActivityLog::whereBetween('created_at', [$startDate, $endDate])->count();
            
            $topActions = ActivityLog::selectRaw('action, COUNT(*) as count')
                ->whereBetween('created_at', [$startDate, $endDate])
                ->groupBy('action')
                ->orderBy('count', 'desc')
                ->limit(10)
                ->get();

            $planDistribution = Subscription::join('subscription_plans', 'subscriptions.subscription_plan_id', '=', 'subscription_plans.id')
                ->selectRaw('subscription_plans.name, COUNT(*) as count, subscription_plans.price_chf')
                ->where('subscriptions.status', 'active')
                ->groupBy('subscription_plans.name', 'subscription_plans.price_chf')
                ->get();

            $recentInvoices = Invoice::with(['organization', 'subscription.subscriptionPlan'])
                ->whereBetween('created_at', [$startDate, $endDate])
                ->orderBy('created_at', 'desc')
                ->limit(15)
                ->get();

            $data = [
                'month' => $startDate->locale('fr')->isoFormat('MMMM YYYY'),
                'period_start' => $startDate->format('d/m/Y'),
                'period_end' => $endDate->format('d/m/Y'),
                'generated_at' => now()->format('d/m/Y H:i'),
                'total_members' => $totalMembers,
                'new_members' => $newMembers,
                'active_subscriptions' => $activeSubscriptions,
                'new_subscriptions' => $newSubscriptions,
                'expired_subscriptions' => $expiredSubscriptions,
                'total_revenue' => $totalRevenue,
                'pending_revenue' => $pendingRevenue,
                'invoices_count' => $invoicesCount,
                'paid_invoices' => $paidInvoices,
                'payment_rate' => $invoicesCount > 0 ? round(($paidInvoices / $invoicesCount) * 100, 2) : 0,
                'activity_count' => $activityCount,
                'top_organizations' => $topOrganizations,
                'top_actions' => $topActions,
                'plan_distribution' => $planDistribution,
                'recent_invoices' => $recentInvoices,
            ];

            $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadView('reports.monthly', $data)
                ->setPaper('a4', 'portrait');

            $filename = 'Rapport_Mensuel_' . $startDate->format('Y-m') . '.pdf';

            return $pdf->download($filename);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur: ' . $e->getMessage()
            ], 500);
        }
    }
}