<?php

namespace App\Console;

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;

class Kernel extends ConsoleKernel
{
    /**
     * Define the application's command schedule.
     */
    protected function schedule(Schedule $schedule): void
    {
        // Envoie les rappels de paiement tous les jours à 9h00
        $schedule->command('invoices:send-reminders')
                 ->dailyAt('09:00')
                 ->timezone('Europe/Zurich');
        
        // Optionnel - Mettre à jour les factures en retard tous les jours à minuit
        $schedule->call(function () {
            \App\Models\Invoice::where('status', 'pending')
                ->whereDate('due_date', '<', now())
                ->update(['status' => 'overdue']);
        })->dailyAt('00:00')->timezone('Europe/Zurich');
        
        // Optionnel - Expirer les abonnements tous les jours à minuit
        $schedule->call(function () {
            \App\Models\Subscription::where('status', 'active')
                ->whereDate('end_date', '<', now())
                ->update(['status' => 'expired']);
        })->dailyAt('00:00')->timezone('Europe/Zurich');
    }

    /**
     * Register the commands for the application.
     */
    protected function commands(): void
    {
        $this->load(__DIR__.'/Commands');

        require base_path('routes/console.php');
    }
}