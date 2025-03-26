<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;

class ResetPasswordOTP extends Mailable
{
    use Queueable, SerializesModels;

    public $otp;
    public $username;

    /**
     * Create a new message instance.
     */
    public function __construct($otp, $username)
    {
        $this->otp = $otp;
        $this->username = $username;
    }

    /**
     * Atur subject email
     */
    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Reset Password OTP - Overtime Connect'
        );
    }

    /**
     * Kirim konten email
     */
    public function content(): Content
    {
        return new Content(
            view: 'emails.reset_password_otp',
            with: [
                'otp' => $this->otp,
                'username' => $this->username,
            ]
        );
    }
}
