<?php
namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use App\Models\GunApplication;

class GunApplicationStatusNotification extends Notification implements ShouldQueue
{
    use Queueable;

    protected $application;
    protected $oldStatus;
    protected $newStatus;
		protected $template;
		protected $variables;

    public function __construct(GunApplication $application, string $oldStatus, string $newStatus, $template = null, array $variables = [])
    {
        $this->application = $application;
        $this->oldStatus = $oldStatus;
        $this->newStatus = $newStatus;
				$this->template = $template;
				$this->variables = $variables;
    }

    public function via($notifiable)
    {
        return ['mail', 'database', 'sms'];
    }

    public function toMail($notifiable)
    {
				$subject = $this->template->subject ?? 'Gun License Application Status Update';
				$message = $this->template->email_template ?? "Your gun license application status has been updated to {$this->newStatus}.";
				if ($this->variables) {
						foreach ($this->variables as $key => $value) {
								$message = str_replace("{{{$key}}}", $value, $message);
						}
				}
				$message = str_replace('{{application_number}}', $this->application->application_number, $message);
				
				return (new MailMessage)
						->subject($subject)
						->line($message)
						->action('View Application', route('gun-applications.show', $this->application->id))
						->line('Thank you for using our application system!');
    }

		public function toSms($notifiable)
		{
				$message = $this->template->sms_template ?? "Your gun license application status has been updated to {$this->newStatus}.";
				if ($this->variables) {
						foreach ($this->variables as $key => $value) {
								$message = str_replace("{{{$key}}}", $value, $message);
						}
				}
				$message = str_replace('{{application_number}}', $this->application->application_number, $message);
				return $message;
		}

    public function toArray($notifiable)
    {
        return [
            'application_id' => $this->application->id,
            'reference_number' => $this->application->reference_number,
            'old_status' => $this->oldStatus,
            'new_status' => $this->newStatus,
            'updated_at' => now(),
        ];
    }
}