<?php
namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use App\Models\EscalationLog;

class EscalationNotification extends Notification implements ShouldQueue
{
    use Queueable;

    protected $model;
    protected $escalationLog;
		protected $template;
		protected $variables;

    public function __construct($model, EscalationLog $escalationLog, $template = null, array $variables = [])
    {
        $this->model = $model;
        $this->escalationLog = $escalationLog;
				$this->template = $template;
				$this->variables = $variables;
    }

    public function via($notifiable)
    {
        return ['mail', 'database'];
    }

    public function toMail($notifiable)
    {
				$subject = $this->template->subject ?? 'Escalation Alert';
				$message = $this->template->email_template ?? "An item ({$this->model->id}) requires your attention. Reason: {$this->escalationLog->escalation_reason}.";
				
				if ($this->variables) {
						foreach ($this->variables as $key => $value) {
								$message = str_replace("{{{$key}}}", $value, $message);
						}
				}
				
				return (new MailMessage)
						->subject($subject)
						->line($message)
						->action('View Item', $this->getModelUrl())
						->line('Please take action as soon as possible!');
    }

		public function toSms($notifiable)
		{
				$message = $this->template->sms_template ?? "Escalation Alert: Item ({$this->model->id}) requires attention. Reason: {$this->escalationLog->escalation_reason}.";
				
				if ($this->variables) {
						foreach ($this->variables as $key => $value) {
								$message = str_replace("{{{$key}}}", $value, $message);
						}
				}
				
				return $message;
		}

    public function toArray($notifiable)
    {
        return [
            'escalation_id' => $this->escalationLog->id,
            'model_type' => get_class($this->model),
            'model_id' => $this->model->id,
            'reason' => $this->escalationLog->escalation_reason,
        ];
    }

    private function getModelUrl(): string
    {
        // Return appropriate URL based on model type
        $modelName = strtolower(class_basename($this->model));
        return url("/{$modelName}s/{$this->model->id}");
    }
}