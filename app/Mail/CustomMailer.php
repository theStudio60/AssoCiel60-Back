<?php

namespace App\Mail;

use Symfony\Component\Mailer\Transport\Smtp\EsmtpTransportFactory;
use Symfony\Component\Mailer\Transport\Smtp\Stream\SocketStream;

class CustomMailer
{
    public static function disableSSLVerification($transport)
    {
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
    }
}