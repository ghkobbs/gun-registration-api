<?php
namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use App\Models\CommunityAlert;

class CommunityAlertNotification extends Notification implements ShouldQueue
{
    use Queueable;

    protected $alert;

    public function __construct(CommunityAlert $alert)
    {
        $this->alert = $alert;
    }

    public function via($notifiable)
    {
        return ['mail', 'database', 'sms'];
    }

    public function toMail($notifiable)
    {
        return (new MailMessage)
            ->subject('Community Alert: ' . $this->alert->title)
            ->line($this->alert->message)
            ->line('Severity: ' . ucfirst($this->alert->severity))
            ->action('View Alert', url('/alerts/' . $this->alert->id));
    }

		public function toSms($notifiable)
		{
				// Return SMS message text
				return "Alert: {$this->alert->title}. {$this->alert->message}";
		}

    public function toArray($notifiable)
    {
        return [
            'alert_id' => $this->alert->id,
            'title' => $this->alert->title,
            'message' => $this->alert->message,
            'severity' => $this->alert->severity,
            'type' => $this->alert->alert_type,
        ];
    }
}