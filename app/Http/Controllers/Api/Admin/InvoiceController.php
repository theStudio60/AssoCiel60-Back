<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Invoice;
use Illuminate\Support\Facades\Validator;
use Barryvdh\DomPDF\Facade\Pdf;
use App\Mail\InvoicePaidMail;
use Illuminate\Support\Facades\Mail;
use App\Mail\PaymentReminderMail;

class InvoiceController extends Controller
{
    /**
     * Get all invoices with filters
     */
    public function index(Request $request)
    {
        try {
            $query = Invoice::with(['organization', 'subscription.subscriptionPlan']);

            // Search
            if ($request->has('search') && $request->search) {
                $search = $request->search;
                $query->where(function($q) use ($search) {
                    $q->where('invoice_number', 'like', "%{$search}%")
                      ->orWhereHas('organization', function($q) use ($search) {
                          $q->where('name', 'like', "%{$search}%");
                      });
                });
            }

            // Filter by status
            if ($request->has('status') && $request->status) {
                $query->where('status', $request->status);
            }

            // Filter by date range
            if ($request->has('start_date') && $request->start_date) {
                $query->whereDate('issue_date', '>=', $request->start_date);
            }
            if ($request->has('end_date') && $request->end_date) {
                $query->whereDate('issue_date', '<=', $request->end_date);
            }

            // Sort
            $sortBy = $request->get('sort_by', 'created_at');
            $sortOrder = $request->get('sort_order', 'desc');
            $query->orderBy($sortBy, $sortOrder);

            // Pagination
            $perPage = $request->get('per_page', 15);
            $invoices = $query->paginate($perPage);

            return response()->json([
                'success' => true,
                'invoices' => $invoices
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get invoice stats
     */
    public function stats()
    {
        try {
            $total = Invoice::count();
            $paid = Invoice::where('status', 'paid')->count();
            $pending = Invoice::where('status', 'pending')->count();
            $overdue = Invoice::where('status', 'overdue')->count();

            // Total revenue
            $totalRevenue = Invoice::where('status', 'paid')->sum('total_amount');

            $stats = [
                'total' => $total,
                'paid' => $paid,
                'pending' => $pending,
                'overdue' => $overdue,
                'total_revenue' => $totalRevenue,
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
     * Get single invoice
     */
    public function show($id)
    {
        try {
            $invoice = Invoice::with(['organization', 'subscription.subscriptionPlan'])
                ->findOrFail($id);

            return response()->json([
                'success' => true,
                'invoice' => $invoice
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Facture non trouvée'
            ], 404);
        }
    }

    /**
     * Update invoice status
     */
    public function updateStatus(Request $request, $id)
    {
        $validator = Validator::make($request->all(), [
            'status' => 'required|in:pending,paid,overdue,canceled',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $invoice = Invoice::findOrFail($id);
            $invoice->update(['status' => $request->status]);

            return response()->json([
                'success' => true,
                'message' => 'Statut mis à jour',
                'invoice' => $invoice->load(['organization', 'subscription.subscriptionPlan'])
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Mark invoice as paid
     */
    public function markAsPaid($id)
    {
        try {
            $invoice = Invoice::findOrFail($id);
            
            $invoice->update([
                'status' => 'paid',
                'paid_at' => now(),
            ]);

            ActivityLog::log(
                'updated',
                "Facture {$invoice->invoice_number} marquée comme payée",
                Invoice::class,
                $invoice->id,
                ['invoice_number' => $invoice->invoice_number, 'amount' => $invoice->total_amount]
            );

            $user = \App\Models\User::where('organization_id', $invoice->organization_id)->first();

             if ($user) {
                try {
                    Mail::to($user->email)->send(new InvoicePaidMail(
                        $invoice,
                        $user,
                        $invoice->organization
                    ));
                } catch (\Exception $e) {
                    \Log::error('Email invoice paid error: ' . $e->getMessage());
                }
            }
            return response()->json([
                'success' => true,
                'message' => 'Facture marquée comme payée',
                'invoice' => $invoice->load(['organization', 'subscription.subscriptionPlan'])
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Send invoice reminder
     */
    public function sendReminder($id)
    {
        try {
            $invoice = Invoice::with(['organization'])->findOrFail($id);
            
            $user = \App\Models\User::where('organization_id', $invoice->organization_id)->first();

            if ($user) {
                Mail::to($user->email)->send(new PaymentReminderMail(
                    $invoice,
                    $user,
                    $invoice->organization
                ));
                
                return response()->json([
                    'success' => true,
                    'message' => 'Rappel envoyé avec succès'
                ]);
            }
            
            return response()->json([
                'success' => true,
                'message' => 'Rappel envoyé',
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Export invoices to CSV
     */
    public function export(Request $request)
    {
        try {
            $invoices = Invoice::with(['organization', 'subscription.subscriptionPlan'])->get();

            $filename = 'factures_' . now()->format('Y-m-d_His') . '.csv';
            
            $headers = [
                'Content-Type' => 'text/csv; charset=UTF-8',
                'Content-Disposition' => "attachment; filename=\"{$filename}\"",
            ];

            $callback = function() use ($invoices) {
                $file = fopen('php://output', 'w');
                
                // UTF-8 BOM
                fprintf($file, chr(0xEF).chr(0xBB).chr(0xBF));
                
                // Header
                fputcsv($file, [
                    'Numéro',
                    'Organisation',
                    'Plan',
                    'Montant',
                    'Devise',
                    'Statut',
                    'Date émission',
                    'Date échéance',
                    'Date paiement',
                ], ';');

                // Data
                foreach ($invoices as $inv) {
                    fputcsv($file, [
                        $inv->invoice_number,
                        $inv->organization->name,
                        $inv->subscription->subscriptionPlan->name ?? 'N/A',
                        $inv->total_amount,
                        $inv->currency,
                        $inv->status,
                        $inv->issue_date->format('d/m/Y'),
                        $inv->due_date->format('d/m/Y'),
                        $inv->paid_at ? $inv->paid_at->format('d/m/Y') : 'Non payé',
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
     * Download invoice PDF
     */
    public function downloadPdf($id)
    {
        try {
            $invoice = Invoice::with(['organization', 'subscription.subscriptionPlan'])
                ->findOrFail($id);

            $pdf = Pdf::loadView('invoices.pdf', ['invoice' => $invoice])
                ->setPaper('a4', 'portrait');

            return $pdf->download('Facture_' . $invoice->invoice_number . '.pdf');
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur: ' . $e->getMessage()
            ], 500);
        }
    }
}