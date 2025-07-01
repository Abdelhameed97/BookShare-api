<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class VerifyEmailCustom extends Mailable
{
    use Queueable, SerializesModels;

    public $user;
    public $verifyUrl;

    public function __construct($user, $verifyUrl)
    {
        $this->user = $user;

        // ✅ نبعته مباشرة للباك إند، و Laravel هيتولى باقي العملية (fulfill, token, redirect)
        $this->verifyUrl = $verifyUrl;
    }

    public function build()
    {
        return $this->subject('Verify Your Email Address')
                    ->view('emails.verify')
                    ->with([
                        'user' => $this->user,
                        'url' => $this->verifyUrl,
                    ]);
    }
}
