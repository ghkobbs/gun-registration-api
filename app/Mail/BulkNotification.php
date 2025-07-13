<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class BulkNotification extends Mailable
{
    use Queueable, SerializesModels;

		protected $email;
		public $subject;
		protected $message;

    /**
     * Create a new message instance.
     */
    public function __construct(String $subject, String $message, String $email)
		{
				$this->email = $email;
				$this->subject = $subject;
				$this->message = $message;
		}

    /**
     * Get the message envelope.
     */
    public function envelope(): Envelope
    {
				return new Envelope(
						subject: $this->subject,
				);
    }

    /**
     * Get the message content definition.
     */
    public function content(): Content
    {
				return new Content(
						view: 'emails.bulk_notification',
						with: [
								'message' => $this->message,
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
