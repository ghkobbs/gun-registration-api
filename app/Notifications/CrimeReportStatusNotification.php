<?php
namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use App\Models\CrimeReport;

class CrimeReportStatusNotification extends Notification implements ShouldQueue
{
    use Queueable;

    protected $crimeReport;
    protected $oldStatus;
    protected $newStatus;
		protected $template;
		protected $variables;

    public function __construct(CrimeReport $crimeReport, string $oldStatus, string $newStatus, $template = null, array $variables = [])
    {
        $this->crimeReport = $crimeReport;
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
				$subject = $this->template->subject ?? 'Crime Report Status Update';
				$message = $this->template->email_template ?? "Your crime report (Ref: {$this->crimeReport->reference_number}) status has been updated to {$this->newStatus}.";
				if ($this->variables) {
						foreach ($this->variables as $key => $value) {
								$message = str_replace("{{{$key}}}", $value, $message);
						}
					}
				$message = str_replace('{{crime_report_reference}}', $this->crimeReport->reference_number, $message);

				return (new MailMessage)
						->subject($subject)
						->line($message)
						->action('View Report', route('crime-reports.show', $this->crimeReport->id))
						->line('Thank you for using our reporting system!');
    }

		public function toSms($notifiable)
		{
				$message = $this->template->sms_template ?? "Your crime report (Ref: {$this->crimeReport->reference_number}) status has been updated to {$this->newStatus}.";
				if ($this->variables) {
						foreach ($this->variables as $key => $value) {
								$message = str_replace("{{{$key}}}", $value, $message);
						}
					}
				$message = str_replace('{{crime_report_reference}}', $this->crimeReport->reference_number, $message);

				return $message;
		}

    public function toArray($notifiable)
    {
        return [
            'crime_report_id' => $this->crimeReport->id,
            'reference_number' => $this->crimeReport->reference_number,
            'old_status' => $this->oldStatus,
            'new_status' => $this->newStatus,
            'updated_at' => now(),
        ];
    }
}