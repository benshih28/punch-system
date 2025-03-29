<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;
class ForgotPasswordMail extends Mailable
{
    use Queueable, SerializesModels;

    public $newPassword;

    public function __construct($newPassword)
    {
        $this->newPassword = $newPassword;
    }

    public function build()
    {
        return $this->subject('Dacall 密碼通知')
            ->view('emails.forgot-password')  //呼叫forgot-password.blade.php
            ->with([
                'newPassword' => $this->newPassword,
                'loginUrl' => url('http://localhost:5173/login'),
            ]);
    }
}