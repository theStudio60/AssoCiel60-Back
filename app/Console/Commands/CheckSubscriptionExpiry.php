<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Subscription;
use App\Models\User;
use App\Models\ActivityLog;
use App\Models\EmailSetting;
use Carbon\Carbon;

class CheckSubscriptionExpiry extends Command
{
    protected $signature = 'subscriptions:check-expiry';
    protected $description = 'Vérifie les abonnements qui vont expirer et envoie des alertes';

    public function handle()
    {
        $this->info('🔔 Vérification des abonnements qui expirent bientôt...');

        // Abonnements qui expirent dans 30 jours
        $expiringSoon = Subscription::where('status', 'active')
            ->whereBetween('end_date', [now()->addDays(29), now()->addDays(31)])
            ->with(['organization', 'subscriptionPlan'])
            ->get();

        // Abonnements qui expirent dans 7 jours
        $expiringVeryS = Subscription::where('status', 'active')
            ->whereBetween('end_date', [now()->addDays(6), now()->addDays(8)])
            ->with(['organization', 'subscriptionPlan'])
            ->get();

        // Abonnements expirés aujourd'hui
        $expiredToday = Subscription::where('status', 'active')
            ->whereDate('end_date', '<=', now())
            ->with(['organization', 'subscriptionPlan'])
            ->get();

        $sent30Days = 0;
        $sent7Days = 0;
        $expired = 0;

        // Alertes 30 jours
        foreach ($expiringSoon as $subscription) {
            $user = User::where('organization_id', $subscription->organization_id)->first();
            if ($user) {
                try {
                    \Mail::to($user->email)->send(
                        new \App\Mail\ExpiryWarningMail($subscription, $user, 30)
                    );
                    $sent30Days++;
                    $this->info("📧 Alerte 30j envoyée: {$subscription->organization->name}");
                } catch (\Exception $e) {
                    $this->warn("⚠️  Email non envoyé: {$e->getMessage()}");
                }
            }
        }

        // Alertes 7 jours
        foreach ($expiringVeryS as $subscription) {
            $user = User::where('organization_id', $subscription->organization_id)->first();
            if ($user) {
                try {
                    \Mail::to($user->email)->send(
                        new \App\Mail\ExpiryWarningMail($subscription, $user, 7)
                    );
                    $sent7Days++;
                    $this->info("📧 Alerte 7j envoyée: {$subscription->organization->name}");
                } catch (\Exception $e) {
                    $this->warn("⚠️  Email non envoyé: {$e->getMessage()}");
                }
            }
        }

        // Marquer comme expirés
        foreach ($expiredToday as $subscription) {
            $subscription->update(['status' => 'expired']);
            
            ActivityLog::log(
                'subscription_expired',
                "Abonnement expiré pour {$subscription->organization->name}",
                Subscription::class,
                $subscription->id
            );

            $expired++;
            $this->warn("⏰ Abonnement expiré: {$subscription->organization->name}");
        }

        $this->info("\n📊 Résumé:");
        $this->info("📧 {$sent30Days} alertes 30 jours envoyées");
        $this->info("📧 {$sent7Days} alertes 7 jours envoyées");
        $this->info("⏰ {$expired} abonnements expirés");

        return 0;
    }
}