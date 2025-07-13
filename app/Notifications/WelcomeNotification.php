<?php
	namespace App\Notifications;

	use App\Models\CommunityAlert;
	use Illuminate\Bus\Queueable;
	use Illuminate\Contracts\Queue\ShouldQueue;
	use Illuminate\Notifications\Messages\MailMessage;
	use Illuminate\Notifications\Notification;
	use Illuminate\Queue\SerializesModels;

	class WelcomeNotification extends Notification implements ShouldQueue
	{
		use Queueable, SerializesModels;

		protected $template;
		protected $variables;

		public function __construct($template = null, array $variables = [])
		{
			$this->template = $template;
			$this->variables = $variables;
		}

		public function via($notifiable)
		{
			return ['mail', 'database', 'sms'];
		}

		public function toMail($notifiable)
		{
			$subject = $this->template->subject ?? 'Welcome to Our Service';
			$message = $this->template->email_template ?? 'Thank you for joining us!';

			if ($this->variables) {
				foreach ($this->variables as $key => $value) {
					$message = str_replace("{{{$key}}}", $value, $message);
				}
			}

			return (new MailMessage)
				->subject($subject)
				->line($message)
				->action('Get Started', url('/'))
				->line('Thank you for using our service!');
		}

		public function toSms($notifiable)
		{
			$message = $this->template->sms_template ?? 'Welcome! Thank you for joining us.';

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
				'message' => $this->template->email_template ?? 'Welcome to our service!',
				'subject' => $this->template->subject ?? 'Welcome',
			];
		}
	}