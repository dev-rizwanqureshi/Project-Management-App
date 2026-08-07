<?php

namespace App\Mail;

use App\Models\Invitation;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class InvitationMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public Invitation $invitation,
        public string $token,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: "You're invited to join {$this->invitation->company->name} on Riraa",
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.invitations.invite',
            text: 'emails.invitations.invite-text',
            with: [
                'invitationUrl' => route('invitations.show', $this->token),
            ],
        );
    }
}
