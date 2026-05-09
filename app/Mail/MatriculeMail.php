<?php

namespace App\Mail;

use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Str;

class MatriculeMail extends Mailable
{
    use Queueable, SerializesModels;

    public $user;
    public $matricule;

    /**
     * Create a new message instance.
     */
    public function __construct(User $user, string $matricule)
    {
        $this->user = $user;
        $this->matricule = $matricule;
    }

    /**
     * Get the message envelope.
     */
    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Votre Matricule Orizon ' . $this->matricule,
        );
    }

    /**
     * Get the message content definition.
     */
    public function content(): Content
    {
        return new Content(
            view: 'emails.matricule',
            with: [
                'user' => $this->user,
                'matricule' => $this->matricule,
            ],
        );
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
