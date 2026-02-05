<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\User;
use App\Models\Organization;
use App\Models\Subscription;
use App\Models\Invoice;
use App\Models\SubscriptionPlan;

class SearchController extends Controller
{
    public function globalSearch(Request $request)
    {
        $query = $request->get('q', '');
        
        if (strlen($query) < 2) {
            return response()->json([
                'success' => true,
                'results' => [],
            ]);
        }

        $results = [
            'members' => [],
            'organizations' => [],
            'subscriptions' => [],
            'invoices' => [],
            'plans' => [],
        ];

        // Membres - Redirige vers la page membres avec recherche pré-remplie
        $results['members'] = User::where('role', 'member')
            ->where(function($q) use ($query) {
                $q->where('first_name', 'like', "%{$query}%")
                  ->orWhere('last_name', 'like', "%{$query}%")
                  ->orWhere('email', 'like', "%{$query}%");
            })
            ->with('organization')
            ->limit(5)
            ->get()
            ->map(function($user) {
                return [
                    'id' => $user->id,
                    'type' => 'member',
                    'title' => $user->first_name . ' ' . $user->last_name,
                    'subtitle' => $user->email,
                    'organization' => $user->organization->name ?? '',
                    'url' => '/admin/members?search=' . urlencode($user->email),
                ];
            });

        // Organisations - Redirige vers la page membres avec recherche organisation
        $results['organizations'] = Organization::where('name', 'like', "%{$query}%")
            ->limit(5)
            ->get()
            ->map(function($org) {
                return [
                    'id' => $org->id,
                    'type' => 'organization',
                    'title' => $org->name,
                    'subtitle' => $org->address ?? '',
                    'url' => '/admin/members?search=' . urlencode($org->name),
                ];
            });

        // Abonnements - Redirige vers la page abonnements avec recherche
        $results['subscriptions'] = Subscription::with(['organization', 'subscriptionPlan'])
            ->whereHas('organization', function($q) use ($query) {
                $q->where('name', 'like', "%{$query}%");
            })
            ->orWhereHas('subscriptionPlan', function($q) use ($query) {
                $q->where('name', 'like', "%{$query}%");
            })
            ->limit(5)
            ->get()
            ->map(function($sub) {
                return [
                    'id' => $sub->id,
                    'type' => 'subscription',
                    'title' => $sub->organization->name ?? '',
                    'subtitle' => $sub->subscriptionPlan->name ?? '',
                    'status' => $sub->status,
                    'url' => '/admin/subscriptions?search=' . urlencode($sub->organization->name ?? ''),
                ];
            });

        // Factures - Redirige vers la page factures avec recherche par numéro
        $results['invoices'] = Invoice::with('organization')
            ->where('invoice_number', 'like', "%{$query}%")
            ->orWhereHas('organization', function($q) use ($query) {
                $q->where('name', 'like', "%{$query}%");
            })
            ->limit(5)
            ->get()
            ->map(function($invoice) {
                return [
                    'id' => $invoice->id,
                    'type' => 'invoice',
                    'title' => $invoice->invoice_number,
                    'subtitle' => $invoice->organization->name ?? '',
                    'amount' => $invoice->total_amount . ' ' . $invoice->currency,
                    'status' => $invoice->status,
                    'url' => '/admin/invoices?search=' . urlencode($invoice->invoice_number),
                ];
            });

        // Packs - Redirige vers la page plans avec recherche
        $results['plans'] = SubscriptionPlan::where('name', 'like', "%{$query}%")
            ->orWhere('description', 'like', "%{$query}%")
            ->limit(5)
            ->get()
            ->map(function($plan) {
                return [
                    'id' => $plan->id,
                    'type' => 'plan',
                    'title' => $plan->name,
                    'subtitle' => $plan->description,
                    'price' => $plan->price_chf . ' CHF',
                    'url' => '/admin/plans?search=' . urlencode($plan->name),
                ];
            });

        return response()->json([
            'success' => true,
            'results' => $results,
        ]);
    }
}