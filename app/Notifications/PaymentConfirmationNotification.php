<?php
namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use App\Models\Payment;

class PaymentConfirmationNotification extends Notification implements ShouldQueue
{
    use Queueable;

    protected $payment;
		protected $template;
		protected $variables;

    public function __construct(Payment $payment, $template = null, array $variables = [])
    {
        $this->payment = $payment;
				$this->template = $template;
				$this->variables = $variables;
    }

    public function via($notifiable)
    {
        return ['mail', 'database', 'sms'];
    }

    public function toMail($notifiable)
    {
				$subject = $this->template->subject ?? 'Payment Confirmation';
				$message = $this->template->email_template ?? "Your payment of GHS {$this->payment->amount} has been successfully processed. " .
					"Payment Reference: {$this->payment->payment_reference}. Thank you for your payment.";
				if ($this->variables) {
						foreach ($this->variables as $key => $value) {
								$message = str_replace("{{{$key}}}", $value, $message);
						}
					}

				$message = str_replace('{{payment_reference}}', $this->payment->payment_reference, $message);

				return (new MailMessage)
						->subject($subject)
						->line($message)
						->action('View Payment', route('payments.show', $this->payment->id))
						->line('Thank you for your payment!');
    }

		public function toSms($notifiable)
		{
				$message = $this->template->sms_template ?? "Payment Confirmation: Your payment of GHS {$this->payment->amount} has been successfully processed. " .
					"Payment Reference: {$this->payment->payment_reference}. Thank you for your payment.";
				if ($this->variables) {
						foreach ($this->variables as $key => $value) {
								$message = str_replace("{{{$key}}}", $value, $message);
						}
					}

				$message = str_replace('{{payment_reference}}', $this->payment->payment_reference, $message);

				return $message;
		}

    public function toArray($notifiable)
    {
        return [
            'payment_id' => $this->payment->id,
            'payment_reference' => $this->payment->payment_reference,
            'amount' => $this->payment->amount,
            'payment_method' => $this->payment->payment_method,
            'processed_at' => $this->payment->processed_at,
        ];
    }
}