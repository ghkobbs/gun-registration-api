<?php
namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use App\Models\CommunityAlert;

class EmergencyAlertNotification extends Notification implements ShouldQueue
{
    use Queueable;

    protected $alert;

    public function __construct(CommunityAlert $alert)
    {
        $this->alert = $alert;
    }

    public function via($notifiable)
    {
        return ['mail', 'database', 'sms', 'broadcast'];
    }

    public function toMail($notifiable)
    {
        return (new MailMessage)
            ->subject('🚨 EMERGENCY ALERT: ' . $this->alert->title)
            ->line('⚠️ URGENT: ' . $this->alert->message)
            ->line('Severity: ' . strtoupper($this->alert->severity))
            ->line('Location: ' . $this->alert->location_name)
            ->line('Please take immediate action and stay safe.')
            ->action('View Alert Details', route('alerts.show', $this->alert->id));
    }

		public function toSms($notifiable)
		{
				// Return SMS message text
				return "🚨 EMERGENCY ALERT: {$this->alert->title}. " .
					"⚠️ URGENT: {$this->alert->message}. " .
					"Severity: {$this->alert->severity}. " .
					"Location: {$this->alert->location_name}. " .
					"Please take immediate action and stay safe.";
		}

    public function toArray($notifiable)
    {
        return [
            'alert_id' => $this->alert->id,
            'title' => $this->alert->title,
            'message' => $this->alert->message,
            'severity' => $this->alert->severity,
            'alert_type' => $this->alert->alert_type,
            'location' => $this->alert->location_name,
            'is_emergency' => true,
        ];
    }

    public function toBroadcast($notifiable)
    {
        return [
            'alert_id' => $this->alert->id,
            'title' => $this->alert->title,
            'message' => $this->alert->message,
            'severity' => $this->alert->severity,
            'type' => 'emergency',
        ];
    }
}