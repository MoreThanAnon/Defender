<?php
namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class VerifyEmail extends Mailable
{
    use Queueable, SerializesModels;

    public $user;
    public $verificationUrl;

    public function __construct($user, $verificationUrl)
    {
        $this->user = $user;
        $this->verificationUrl = $verificationUrl;
    }

    public function build()
    {
        return $this->view('emails.verify-email')
                    ->subject('Verify Your Email Address')
                    ->with([
                        'username' => $this->user->username,
                        'verificationUrl' => $this->verificationUrl,
                    ]);
    }
}