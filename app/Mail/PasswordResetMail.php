<?php

namespace App\Mail;

use App\Models\PasswordRecovery;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Address;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

/**
 * Password reset email for Skivsamlingen.
 */
class PasswordResetMail extends Mailable
{
    use Queueable, SerializesModels;

    /**
     * Create a new message instance.
     */
    public function __construct(
        public User $user,
        public PasswordRecovery $recovery
    ) {}

    /**
     * Get the message envelope.
     */
    public function envelope(): Envelope
    {
        return new Envelope(
            from: new Address('no-reply@skivsamlingen.se', 'Skivsamlingen'),
            subject: 'Skivsamlingen: Återställ lösenord',
        );
    }

    /**
     * Get the message content definition.
     */
    public function content(): Content
    {
        return new Content(
            text: 'emails.password-reset',
            with: [
                'resetUrl' => $this->getResetUrl(),
            ],
        );
    }

    /**
     * Get the reset URL for the email.
     */
    public function getResetUrl(): string
    {
        return url("/account/recover/{$this->user->username}/{$this->recovery->hash}");
    }

    /**
     * Get the attachments for the message.
     *
     * @return array<int, \Illuminate\Mail\Mailables\Attachment>
     */
    public function attachments(): array
    {
        return [];
    }
}
