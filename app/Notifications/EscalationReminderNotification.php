<?php
namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use App\Models\EscalationLog;

class EscalationReminderNotification extends Notification implements ShouldQueue
{
    use Queueable;

    protected $escalationLog;

    public function __construct(EscalationLog $escalationLog)
    {
        $this->escalationLog = $escalationLog;
    }

    public function via($notifiable)
    {
        return ['mail', 'database'];
    }

    public function toMail($notifiable)
    {
        $hoursAgo = $this->escalationLog->escalated_at->diffInHours(now());
        
        return (new MailMessage)
            ->subject('Escalation Reminder: Action Required')
            ->line('This is a reminder about an escalated item that requires your attention.')
            ->line('Item: ' . class_basename($this->escalationLog->escalatable_type))
            ->line('Escalated: ' . $hoursAgo . ' hours ago')
            ->line('Reason: ' . $this->escalationLog->escalation_reason)
            ->action('View Item', $this->getModelUrl())
            ->line('Please take action as soon as possible.');
    }

		public function toSms($notifiable)
		{
				// Return SMS message text
				return "Escalation Reminder: An item ({$this->escalationLog->escalatable_id}) requires your attention. " .
					"Escalated: {$this->escalationLog->escalated_at->diffForHumans()} ago. " .
					"Reason: {$this->escalationLog->escalation_reason}. " .
					"Please take action as soon as possible. View at: {$this->getModelUrl()}";
		}

    public function toArray($notifiable)
    {
        return [
            'escalation_id' => $this->escalationLog->id,
            'model_type' => $this->escalationLog->escalatable_type,
            'model_id' => $this->escalationLog->escalatable_id,
            'reason' => $this->escalationLog->escalation_reason,
            'escalated_at' => $this->escalationLog->escalated_at,
            'type' => 'reminder',
        ];
    }

    private function getModelUrl(): string
    {
        $modelType = strtolower(class_basename($this->escalationLog->escalatable_type));
        return url("/{$modelType}s/{$this->escalationLog->escalatable_id}");
    }
}