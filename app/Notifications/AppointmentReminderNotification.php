<?php
namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
// use App\Models\Appointment;

class AppointmentReminderNotification extends Notification implements ShouldQueue
{
    // use Queueable;

    // protected $appointment;
    // protected $reminderType;

    // public function __construct(Appointment $appointment, string $reminderType = '24_hours')
    // {
    //     $this->appointment = $appointment;
    //     $this->reminderType = $reminderType;
    // }

    // public function via($notifiable)
    // {
    //     return ['mail', 'database', 'sms'];
    // }

    // public function toMail($notifiable)
    // {
    //     $timeText = match ($this->reminderType) {
    //         '24_hours' => 'tomorrow',
    //         '2_hours' => 'in 2 hours',
    //         '30_minutes' => 'in 30 minutes',
    //         default => 'soon',
    //     };

    //     return (new MailMessage)
    //         ->subject('Appointment Reminder')
    //         ->line('This is a reminder about your upcoming appointment ' . $timeText . '.')
    //         ->line('Date: ' . $this->appointment->scheduled_at->format('F j, Y'))
    //         ->line('Time: ' . $this->appointment->scheduled_at->format('g:i A'))
    //         ->line('Purpose: ' . $this->appointment->purpose)
    //         ->line('Location: ' . $this->appointment->location)
    //         ->action('View Appointment', route('appointments.show', $this->appointment->id))
    //         ->line('Please arrive 15 minutes early.');
    // }

    // public function toArray($notifiable)
    // {
    //     return [
    //         'appointment_id' => $this->appointment->id,
    //         'scheduled_at' => $this->appointment->scheduled_at,
    //         'purpose' => $this->appointment->purpose,
    //         'location' => $this->appointment->location,
    //         'reminder_type' => $this->reminderType,
    //     ];
    // }
}