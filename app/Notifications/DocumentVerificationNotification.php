<?php
namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use App\Models\Document;

class DocumentVerificationNotification extends Notification implements ShouldQueue
{
    use Queueable;

    protected $document;
    protected $status;
    protected $comments;

    public function __construct(Document $document, string $status, string | null $comments)
    {
        $this->document = $document;
        $this->status = $status;
        $this->comments = $comments;
    }

    public function via($notifiable)
    {
        return ['mail', 'database'];
    }

    public function toMail($notifiable)
    {
        $message = (new MailMessage)
            ->subject('Document Verification Update')
            ->line('Your document verification status has been updated.')
            ->line('Document: ' . $this->document->document_type)
            ->line('Status: ' . ucfirst($this->status));

        if ($this->comments) {
            $message->line('Comments: ' . $this->comments ?? 'No comments provided');
        }

        if ($this->status === 'rejected') {
            $message->line('Please upload a new document or contact support for assistance.')
                   ->action('Upload New Document', route('applications.documents.upload', $this->document->gun_application_id));
        } else {
            $message->action('View Application', route('applications.show', $this->document->gun_application_id));
        }

        return $message;
    }

		public function toSms($notifiable)
		{
				// Return SMS message text
				return "Document Verification Update: Your document ({$this->document->document_type}) status is now {$this->status}. " .
					($this->comments ? "Comments: {$this->comments}" : "No comments provided") . 
					($this->status === 'rejected' ? " Please upload a new document." : "");
		}

    public function toArray($notifiable)
    {
        return [
            'document_id' => $this->document->id,
            'document_type' => $this->document->document_type,
            'status' => $this->status,
            'comments' => $this->comments,
            'application_id' => $this->document->gun_application_id,
        ];
    }
}