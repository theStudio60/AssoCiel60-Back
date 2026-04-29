<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class NewArticleMail extends Mailable
{
    use Queueable, SerializesModels;

    public $user;
    public $article;

    public function __construct($user, $article)
    {
        $this->user = $user;
        $this->article = $article;
    }

    public function build()
    {
        return $this->subject('Nouvel article publié sur Alprail : ' . $this->article['title'])
                    ->view('emails.new-article');
    }
}