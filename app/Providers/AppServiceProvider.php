<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Schema;
use Symfony\Component\Mailer\Transport\Smtp\Stream\SocketStream;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        //  Override la config mail avec les valeurs de la DB (interface admin)
        try {
            if (Schema::hasTable('email_settings')) {
                $host = \App\Models\EmailSetting::get('smtp_host');
                $port = \App\Models\EmailSetting::get('smtp_port');
                $username = \App\Models\EmailSetting::get('smtp_username');
                $password = \App\Models\EmailSetting::get('smtp_password');
                $fromEmail = \App\Models\EmailSetting::get('smtp_from_email');
                $fromName = \App\Models\EmailSetting::get('smtp_from_name');

                if ($host) config(['mail.mailers.smtp.host' => $host]);
                if ($port) config(['mail.mailers.smtp.port' => $port]);
                if ($username) config(['mail.mailers.smtp.username' => $username]);
                if ($password) config(['mail.mailers.smtp.password' => $password]);
                if ($fromEmail) config(['mail.from.address' => $fromEmail]);
                if ($fromName) config(['mail.from.name' => $fromName]);
            }
        } catch (\Exception $e) {
            \Log::error('Erreur override config mail: ' . $e->getMessage());
        }

        // Fix SSL uniquement en local (XAMPP)
        if (app()->environment('local')) {
            Mail::extend('smtp', function (array $config = []) {
                $factory = new \Symfony\Component\Mailer\Transport\Smtp\EsmtpTransportFactory();
                $transport = $factory->create(new \Symfony\Component\Mailer\Transport\Dsn(
                    'smtp',
                    $config['host'],
                    $config['username'] ?? null,
                    $config['password'] ?? null,
                    $config['port'] ?? null,
                    $config
                ));

                $stream = $transport->getStream();
                if ($stream instanceof SocketStream) {
                    $stream->setStreamOptions([
                        'ssl' => [
                            'verify_peer' => false,
                            'verify_peer_name' => false,
                            'allow_self_signed' => true,
                        ],
                    ]);
                }

                return $transport;
            });
        }
    }
}