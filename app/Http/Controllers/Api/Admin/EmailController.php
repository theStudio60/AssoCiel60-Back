<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Validator;
use App\Models\EmailSetting;
use App\Mail\WelcomeMail;
use App\Mail\SubscriptionConfirmedMail;
use App\Mail\PaymentReminderMail;
use App\Mail\InvoicePaidMail;

class EmailController extends Controller
{
    /**
     * Get email settings
     */
    public function getSettings()
    {
        try {
            $settings = [
                'welcome_enabled' => EmailSetting::get('welcome_enabled', true),
                'welcome_subject' => EmailSetting::get('welcome_subject', 'Bienvenue chez Alprail'),
                'subscription_enabled' => EmailSetting::get('subscription_enabled', true),
                'subscription_subject' => EmailSetting::get('subscription_subject', 'Votre abonnement est confirmé'),
                'reminder_enabled' => EmailSetting::get('reminder_enabled', true),
                'reminder_subject' => EmailSetting::get('reminder_subject', 'Rappel de paiement - Facture {invoice_number}'),
                'reminder_days_before' => EmailSetting::get('reminder_days_before', 7),
                'payment_enabled' => EmailSetting::get('payment_enabled', true),
                'payment_subject' => EmailSetting::get('payment_subject', 'Paiement reçu - Facture {invoice_number}'),
                'smtp_host' => env('MAIL_HOST', 'smtp.gmail.com'),
                'smtp_port' => env('MAIL_PORT', '587'),
                'smtp_from_email' => env('MAIL_FROM_ADDRESS', 'noreply@alprail.net'),
                'smtp_from_name' => env('MAIL_FROM_NAME', 'Alprail'),
            ];

            // Convertir les booléens
            $settings['welcome_enabled'] = filter_var($settings['welcome_enabled'], FILTER_VALIDATE_BOOLEAN);
            $settings['subscription_enabled'] = filter_var($settings['subscription_enabled'], FILTER_VALIDATE_BOOLEAN);
            $settings['reminder_enabled'] = filter_var($settings['reminder_enabled'], FILTER_VALIDATE_BOOLEAN);
            $settings['payment_enabled'] = filter_var($settings['payment_enabled'], FILTER_VALIDATE_BOOLEAN);

            return response()->json([
                'success' => true,
                'settings' => $settings
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Update email settings
     */
    public function updateSettings(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'welcome_enabled' => 'boolean',
            'welcome_subject' => 'required|string|max:255',
            'subscription_enabled' => 'boolean',
            'subscription_subject' => 'required|string|max:255',
            'reminder_enabled' => 'boolean',
            'reminder_subject' => 'required|string|max:255',
            'reminder_days_before' => 'required|integer|min:1|max:30',
            'payment_enabled' => 'boolean',
            'payment_subject' => 'required|string|max:255',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            // Sauvegarder chaque paramètre
            EmailSetting::set('welcome_enabled', $request->welcome_enabled ? '1' : '0');
            EmailSetting::set('welcome_subject', $request->welcome_subject);
            EmailSetting::set('subscription_enabled', $request->subscription_enabled ? '1' : '0');
            EmailSetting::set('subscription_subject', $request->subscription_subject);
            EmailSetting::set('reminder_enabled', $request->reminder_enabled ? '1' : '0');
            EmailSetting::set('reminder_subject', $request->reminder_subject);
            EmailSetting::set('reminder_days_before', $request->reminder_days_before);
            EmailSetting::set('payment_enabled', $request->payment_enabled ? '1' : '0');
            EmailSetting::set('payment_subject', $request->payment_subject);

            return response()->json([
                'success' => true,
                'message' => 'Paramètres emails mis à jour'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Send test email
     */
    public function sendTest(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'email' => 'required|email',
            'type' => 'required|in:welcome,subscription,reminder,payment',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $testUser = (object)[
                'first_name' => 'Test',
                'last_name' => 'User',
                'email' => $request->email
            ];

            $testOrganization = (object)[
                'name' => 'Organisation Test',
                'address' => 'Route de Test 1',
                'zip_code' => '1000',
                'city' => 'Lausanne'
            ];

            switch ($request->type) {
                case 'welcome':
                    Mail::to($request->email)->send(new WelcomeMail($testUser, $testOrganization));
                    $message = 'Email de bienvenue envoyé';
                    break;

                case 'subscription':
                    $testSubscription = (object)[
                        'subscriptionPlan' => (object)[
                            'name' => 'Pack Test',
                            'price_chf' => '50.00'
                        ],
                        'start_date' => now(),
                        'end_date' => now()->addYear()
                    ];
                    Mail::to($request->email)->send(new SubscriptionConfirmedMail($testSubscription, $testUser, $testOrganization));
                    $message = 'Email de confirmation d\'abonnement envoyé';
                    break;

                case 'reminder':
                    $testInvoice = (object)[
                        'invoice_number' => 'INV-TEST-001',
                        'total_amount' => '50.00',
                        'issue_date' => now(),
                        'due_date' => now()->addDays(7)
                    ];
                    Mail::to($request->email)->send(new PaymentReminderMail($testInvoice, $testUser, $testOrganization));
                    $message = 'Email de rappel de paiement envoyé';
                    break;

                case 'payment':
                    $testInvoice = (object)[
                        'invoice_number' => 'INV-TEST-001',
                        'total_amount' => '50.00',
                        'paid_at' => now()
                    ];
                    Mail::to($request->email)->send(new InvoicePaidMail($testInvoice, $testUser, $testOrganization));
                    $message = 'Email de confirmation de paiement envoyé';
                    break;

                default:
                    throw new \Exception('Type d\'email invalide');
            }

            return response()->json([
                'success' => true,
                'message' => $message
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get email logs
     */
    public function getLogs()
    {
        try {
            $logs = [];
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
}