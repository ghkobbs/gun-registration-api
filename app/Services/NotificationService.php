<?php

namespace App\Services;

use App\Mail\BulkNotification as MailBulkNotification;
use App\Models\User;
use App\Models\GunApplication;
use App\Models\CrimeReport;
use App\Models\EscalationLog;
use App\Models\NotificationTemplate;
use App\Notifications\ApplicationStatusUpdated;
use App\Notifications\EscalationNotification;
use App\Notifications\CrimeReportUpdated;
use App\Notifications\DocumentVerificationNotification;
use App\Notifications\PaymentConfirmation;
use App\Notifications\LicenseExpiryReminder;
use App\Notifications\BulkNotification;
use App\Notifications\CrimeReportStatusNotification;
use App\Notifications\GunApplicationStatusNotification;
use App\Notifications\LicenseExpiryReminderNotification;
use App\Notifications\PaymentConfirmationNotification;
use App\Notifications\WelcomeNotification;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;

class NotificationService
{
    protected $smsService;

    public function __construct(SMSService $smsService)
    {
        $this->smsService = $smsService;
    }

    /**
     * Send application status update notification
     */
    public function sendApplicationStatusUpdate(User $user, GunApplication $application, $oldStatus, $newStatus)
    {
        try {
            // Get notification template
            $template = NotificationTemplate::where('name', 'application_status_update')
                                           ->where('is_active', true)
                                           ->first();

            if (!$template) {
                Log::warning('No template found for application_status_update');
                return false;
            }

            // Prepare variables
            $variables = [
                'user_name' => $user->first_name . ' ' . $user->last_name,
                'application_number' => $application->application_number,
                'old_status' => ucfirst(str_replace('_', ' ', $oldStatus)),
                'new_status' => ucfirst(str_replace('_', ' ', $newStatus)),
                'date' => now()->format('Y-m-d H:i:s'),
                'app_url' => config('app.url'),
            ];

            // Send email notification
            if (in_array($template->type, ['email', 'both']) && $user->email) {
                $user->notify(new GunApplicationStatusNotification($application, $oldStatus, $newStatus, $template, $variables));
            }

            // Send SMS notification
            if (in_array($template->type, ['sms', 'both']) && $user->phone_number) {
                $message = $this->replacePlaceholders($template->sms_template, $variables);
                $this->smsService->sendSMS($user->phone_number, $message);
            }

            // Log the notification
            $this->logNotification($user->id, 'application_status_update', $variables);

            return true;

        } catch (\Exception $e) {
            Log::error('Failed to send application status update notification: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Send escalation notification
     */
    public function sendEscalationNotification(User $user, GunApplication $application, EscalationLog $escalationLog)
    {
        try {
            $template = NotificationTemplate::where('name', 'escalation_notification')
                                           ->where('is_active', true)
                                           ->first();

            if (!$template) {
                return false;
            }

            $variables = [
                'user_name' => $user->first_name . ' ' . $user->last_name,
                'application_number' => $application->application_number,
                'escalation_reason' => $escalationLog->escalation_reason,
                'priority_level' => $application->priority_level,
                'date' => now()->format('Y-m-d H:i:s'),
                'app_url' => config('app.url'),
            ];

            // Send email
            if (in_array($template->type, ['email', 'both']) && $user->email) {
                $user->notify(new EscalationNotification($application, $escalationLog, $template, $variables));
            }

            // Send SMS
            if (in_array($template->type, ['sms', 'both']) && $user->phone_number) {
                $message = $this->replacePlaceholders($template->sms_template, $variables);
                $this->smsService->sendSMS($user->phone_number, $message);
            }

            $this->logNotification($user->id, 'escalation_notification', $variables);

            return true;

        } catch (\Exception $e) {
            Log::error('Failed to send escalation notification: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Send applicant escalation notification
     */
    public function sendApplicantEscalationNotification(User $user, GunApplication $application, EscalationLog $escalationLog)
    {
        try {
            $template = NotificationTemplate::where('name', 'applicant_escalation_notification')
                                           ->where('is_active', true)
                                           ->first();

            if (!$template) {
                return false;
            }

            $variables = [
                'user_name' => $user->first_name . ' ' . $user->last_name,
                'application_number' => $application->application_number,
                'escalation_reason' => $escalationLog->escalation_reason,
                'expected_resolution_time' => '2-3 business days',
                'date' => now()->format('Y-m-d H:i:s'),
                'support_email' => config('mail.support_email', 'support@example.com'),
            ];

            // Send email
            if (in_array($template->type, ['email', 'both']) && $user->email) {
                $user->notify(new EscalationNotification($application, $escalationLog, $template, $variables));
            }

            // Send SMS
            if (in_array($template->type, ['sms', 'both']) && $user->phone_number) {
                $message = $this->replacePlaceholders($template->sms_template, $variables);
                $this->smsService->sendSMS($user->phone_number, $message);
            }

            $this->logNotification($user->id, 'applicant_escalation_notification', $variables);

            return true;

        } catch (\Exception $e) {
            Log::error('Failed to send applicant escalation notification: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Send crime report update notification
     */
    public function sendCrimeReportUpdate(CrimeReport $report, $updateMessage)
    {
        try {
            // Only send if not anonymous and contact info available
            if ($report->is_anonymous || (!$report->reporter_email && !$report->reporter_phone)) {
                return false;
            }

            $template = NotificationTemplate::where('name', 'crime_report_update')
                                           ->where('is_active', true)
                                           ->first();

            if (!$template) {
                return false;
            }

            $variables = [
                'reporter_name' => $report->reporter_name ?: 'Reporter',
                'report_number' => $report->report_number,
                'reference_code' => $report->reference_code,
                'status' => ucfirst(str_replace('_', ' ', $report->status)),
                'update_message' => $updateMessage,
                'date' => now()->format('Y-m-d H:i:s'),
                'ussd_code' => config('app.ussd_code', '*920#'),
            ];

            // Send email if available
            if ($report->reporter_email && in_array($template->type, ['email', 'both'])) {
                // Create a temporary notification for non-user
                $emailData = [
                    'email' => $report->reporter_email,
                    'name' => $report->reporter_name,
                ];
                
								// send notification
								$report->notify(new CrimeReportStatusNotification($report, $updateMessage, $template, $variables));

								// Log email notification
								Log::info('Crime report update email sent', [
										'email' => $report->reporter_email,
										'report_number' => $report->report_number,
								]);
						} else {
								Log::warning('No email provided for crime report update', [
										'report_number' => $report->report_number,
								]);
            }

            // Send SMS if available
            if ($report->reporter_phone && in_array($template->type, ['sms', 'both'])) {
                $message = $this->replacePlaceholders($template->sms_template, $variables);
                $this->smsService->sendSMS($report->reporter_phone, $message);
            }

            $this->logNotification(null, 'crime_report_update', $variables, $report->id);

            return true;

        } catch (\Exception $e) {
            Log::error('Failed to send crime report update notification: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Send document verification notification
     */
    public function sendDocumentVerificationNotification(User $user, $documentTitle, $status, $notes = null)
    {
        try {
            $template = NotificationTemplate::where('name', 'document_verification')
                                           ->where('is_active', true)
                                           ->first();

            if (!$template) {
                return false;
            }

            $variables = [
                'user_name' => $user->first_name . ' ' . $user->last_name,
                'document_title' => $documentTitle,
                'status' => ucfirst($status),
                'notes' => $notes ?: 'No additional notes provided',
                'date' => now()->format('Y-m-d H:i:s'),
                'next_steps' => $this->getDocumentVerificationNextSteps($status),
            ];

            // Send email
            if (in_array($template->type, ['email', 'both']) && $user->email) {
                $user->notify(new DocumentVerificationNotification($documentTitle, $status, $notes, $template, $variables));
            }

            // Send SMS
            if (in_array($template->type, ['sms', 'both']) && $user->phone_number) {
                $message = $this->replacePlaceholders($template->sms_template, $variables);
                $this->smsService->sendSMS($user->phone_number, $message);
            }

            $this->logNotification($user->id, 'document_verification', $variables);

            return true;

        } catch (\Exception $e) {
            Log::error('Failed to send document verification notification: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Send payment confirmation
     */
    public function sendPaymentConfirmation(User $user, $paymentData)
    {
        try {
            $template = NotificationTemplate::where('name', 'payment_confirmation')
                                           ->where('is_active', true)
                                           ->first();

            if (!$template) {
                return false;
            }

            $variables = [
                'user_name' => $user->first_name . ' ' . $user->last_name,
                'payment_reference' => $paymentData['reference'],
                'amount' => number_format($paymentData['amount'], 2),
                'currency' => $paymentData['currency'],
                'description' => $paymentData['description'],
                'payment_method' => $paymentData['payment_method'] ?? 'Online',
                'date' => now()->format('Y-m-d H:i:s'),
                'receipt_url' => $paymentData['receipt_url'] ?? '',
            ];

            // Send email
            if (in_array($template->type, ['email', 'both']) && $user->email) {
                $user->notify(new PaymentConfirmationNotification($paymentData, $template, $variables));
            }

            // Send SMS
            if (in_array($template->type, ['sms', 'both']) && $user->phone_number) {
                $message = $this->replacePlaceholders($template->sms_template, $variables);
                $this->smsService->sendSMS($user->phone_number, $message);
            }

            $this->logNotification($user->id, 'payment_confirmation', $variables);

            return true;

        } catch (\Exception $e) {
            Log::error('Failed to send payment confirmation notification: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Send license expiry reminder
     */
    public function sendLicenseExpiryReminder(User $user, $gunRegistration, $daysUntilExpiry)
    {
        try {
            $template = NotificationTemplate::where('name', 'license_expiry_reminder')
                                           ->where('is_active', true)
                                           ->first();

            if (!$template) {
                return false;
            }

            $variables = [
                'user_name' => $user->first_name . ' ' . $user->last_name,
                'registration_number' => $gunRegistration->registration_number,
                'firearm_type' => $gunRegistration->firearm_type,
                'expiry_date' => $gunRegistration->expiry_date->format('Y-m-d'),
                'days_until_expiry' => $daysUntilExpiry,
                'renewal_url' => config('app.url') . '/gun-registrations/' . $gunRegistration->id . '/renew',
            ];

            // Send email
            if (in_array($template->type, ['email', 'both']) && $user->email) {
                $user->notify(new LicenseExpiryReminderNotification($gunRegistration, $daysUntilExpiry, $template, $variables));
            }

            // Send SMS
            if (in_array($template->type, ['sms', 'both']) && $user->phone_number) {
                $message = $this->replacePlaceholders($template->sms_template, $variables);
                $this->smsService->sendSMS($user->phone_number, $message);
            }

            $this->logNotification($user->id, 'license_expiry_reminder', $variables);

            return true;

        } catch (\Exception $e) {
            Log::error('Failed to send license expiry reminder: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Send bulk notification
     */
    public function sendBulkNotification($recipients, $subject, $message, $type = 'both')
    {
        try {
            $sent = 0;
            $failed = 0;

            foreach ($recipients as $recipient) {
                try {
                    if ($type === 'email' || $type === 'both') {
                        if (isset($recipient['email'])) {
													
                            Mail::to($recipient['email'])
                                ->send(new MailBulkNotification($subject, $message, $recipient));
                        }
                    }

                    if ($type === 'sms' || $type === 'both') {
                        if (isset($recipient['phone'])) {
                            $this->smsService->sendSMS($recipient['phone'], $message);
                        }
                    }

                    $sent++;

                } catch (\Exception $e) {
                    $failed++;
                    Log::error('Failed to send bulk notification to recipient: ' . $e->getMessage());
                }
            }

            // Log bulk notification
            $this->logNotification(null, 'bulk_notification', [
                'subject' => $subject,
                'message' => $message,
                'type' => $type,
                'sent' => $sent,
                'failed' => $failed,
                'total' => count($recipients),
            ]);

            return [
                'sent' => $sent,
                'failed' => $failed,
                'total' => count($recipients),
            ];

        } catch (\Exception $e) {
            Log::error('Failed to send bulk notification: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Send welcome notification to new users
     */
    public function sendWelcomeNotification(User $user)
    {
        try {
            $template = NotificationTemplate::where('name', 'welcome_user')
                                           ->where('is_active', true)
                                           ->first();

            if (!$template) {
                return false;
            }

            $variables = [
                'user_name' => $user->first_name . ' ' . $user->last_name,
                'login_url' => config('app.url') . '/login',
                'support_email' => config('mail.support_email', 'support@example.com'),
                'date' => now()->format('Y-m-d H:i:s'),
            ];

            // Send email
            if (in_array($template->type, ['email', 'both']) && $user->email) {
                $user->notify(new WelcomeNotification($template, $variables));
            }

            // Send SMS
            if (in_array($template->type, ['sms', 'both']) && $user->phone_number) {
                $message = $this->replacePlaceholders($template->sms_template, $variables);
                $this->smsService->sendSMS($user->phone_number, $message);
            }

            return true;

        } catch (\Exception $e) {
            Log::error('Failed to send welcome notification: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Replace placeholders in template with actual values
     */
    private function replacePlaceholders($template, $variables)
    {
        foreach ($variables as $key => $value) {
            $template = str_replace('{{' . $key . '}}', $value, $template);
        }
        return $template;
    }

    /**
     * Get next steps based on document verification status
     */
    private function getDocumentVerificationNextSteps($status)
    {
        switch ($status) {
            case 'verified':
                return 'Your document has been approved. You can proceed with your application.';
            case 'rejected':
                return 'Please upload a new document that meets our requirements.';
            default:
                return 'Your document is under review. We will notify you of the outcome.';
        }
    }

    /**
     * Log notification for audit purposes
     */
    private function logNotification($userId, $type, $variables, $relatedId = null)
    {
        try {
            DB::table('notification_logs')->insert([
                'user_id' => $userId,
                'type' => $type,
                'variables' => json_encode($variables),
                'related_id' => $relatedId,
                'sent_at' => now(),
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to log notification: ' . $e->getMessage());
        }
    }

    /**
     * Get notification statistics
     */
    public function getNotificationStats($dateFrom = null, $dateTo = null)
    {
        try {
            $query = DB::table('notification_logs');

            if ($dateFrom) {
                $query->where('sent_at', '>=', $dateFrom);
            }

            if ($dateTo) {
                $query->where('sent_at', '<=', $dateTo);
            }

            return $query->select('type', DB::raw('count(*) as total'))
                         ->groupBy('type')
                         ->get();

        } catch (\Exception $e) {
            Log::error('Failed to get notification stats: ' . $e->getMessage());
            return collect();
        }
    }
}