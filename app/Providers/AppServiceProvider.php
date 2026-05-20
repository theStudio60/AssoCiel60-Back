<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Mail;
use Symfony\Component\Mailer\Transport\Smtp\Stream\SocketStream;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
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