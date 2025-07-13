<?php
namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class LicenseExpiryReminderNotification extends Notification implements ShouldQueue
{
    use Queueable;

    protected $license;
		protected $expiryDate;
		protected $template;
		protected $variables;

		public function __construct($license, $expiryDate, $template = null, array $variables = [])
		{
				$this->license = $license;
				$this->expiryDate = $expiryDate;
				$this->template = $template;
				$this->variables = $variables;
		}

		public function via($notifiable)
		{
				return ['mail', 'database', 'sms'];
		}

		public function toMail($notifiable)
		{
				$subject = $this->template->subject ?? 'License Expiry Reminder';
				$message = $this->template->email_template ?? "Your license is set to expire on {$this->expiryDate}. Please take action.";
				if ($this->variables) {
						foreach ($this->variables as $key => $value) {
								$message = str_replace("{{{$key}}}", $value, $message);
						}
					}
				$message = str_replace('{{license_number}}', $this->license->number, $message);
				return (new MailMessage)
						->subject($subject)
						->line($message)
						->action('Renew License', route('licenses.renew', $this->license->id))
						->line('Thank you for your attention!');
		}

		public function toSms($notifiable)
		{
				$message = $this->template->sms_template ?? "Your license ({$this->license->number}) is expiring on {$this->expiryDate}. Please renew it.";
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
						'license_id' => $this->license->id,
						'expiry_date' => $this->expiryDate,
						'type' => 'reminder',
				];
				
		}
	}