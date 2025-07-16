<?php

namespace App\Mail;

use App\Models\Accommodation;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Address;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class AccommodationStatusUpdateMail extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;
    
    // Optimisations pour la queue
    public $timeout = 120;
    public $tries = 3;
    public $backoff = 60;

    /**
     * Create a new message instance.
     */
    public function __construct(
        public Accommodation $accommodation
    ) {
        //
    }

    /**
     * Get the message envelope.
     */
    public function envelope(): Envelope
    {
        return new Envelope(
            from: new Address(
                address: config('mail.from.address'),
                name: config('mail.from.name')
            ),
            subject: '[DISPO NUIT] ' . $this->accommodation->name,
            replyTo: config('mail.from.address'),
            tags: ['accommodation-notification', 'status-update'],
            metadata: [
                'accommodation_id' => $this->accommodation->apidae_id,
                'accommodation_name' => $this->accommodation->name,
                'accommodation_status' => $this->accommodation->status,
                'email_type' => 'status_update_notification',
                'sent_date' => now()->toDateTimeString(),
            ],
        );
    }

    /**
     * Get the message content definition.
     */
    public function content(): Content
    {
        return new Content(
            view: 'emails.accommodation-status-update',
            text: 'emails.accommodation-status-update-text',
            with: [
                'accommodation' => $this->accommodation,
                'manageUrl' => $this->accommodation->getManageUrl(),
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
