<?php
namespace App\Services;

use App\Models\CrimeReport;
use App\Models\GunApplication;
use App\Models\EscalationRule;
use App\Models\EscalationLog;
use App\Models\User;
use App\Notifications\ApplicationEscalatedNotification;
use App\Notifications\EscalationReminderNotification;
use Illuminate\Support\Facades\Log;

class EscalationService
{
    protected $notificationService;

    public function __construct(NotificationService $notificationService)
    {
        $this->notificationService = $notificationService;
    }

    /**
     * Start monitoring an application for escalation
     */
    public function startMonitoring(GunApplication $application)
    {
        // Mark application as being monitored
        $application->update(['is_escalated' => false]);

        Log::info("Started escalation monitoring for application: {$application->application_number}");
    }

    /**
     * Check applications that need escalation
     */
    public function checkForEscalations()
    {
        $applications = GunApplication::where('status', 'submitted')
            ->where('is_escalated', false)
            ->get();

        foreach ($applications as $application) {
            $this->evaluateApplicationForEscalation($application);
        }
    }

    /**
     * Evaluate a specific application for escalation
     */
    public function evaluateApplicationForEscalation(GunApplication $application)
    {
        $escalationRules = EscalationRule::where('is_active', true)
            ->orderBy('priority_level', 'desc')
            ->get();

        foreach ($escalationRules as $rule) {
            if ($this->shouldEscalate($application, $rule)) {
                $this->escalateApplication($application, $rule);
                break; // Only apply the first matching rule
            }
        }
    }

		/**
		 * Check if an application should be escalated based on the rule
		 */
		public function checkEscalations(): void
    {
        $activeRules = EscalationRule::where('is_active', true)->get();
        
        foreach ($activeRules as $rule) {
            $this->processRule($rule);
        }
    }

    private function processRule(EscalationRule $rule): void
    {
        // Check crime reports
        CrimeReport::whereNotIn('status', ['resolved', 'closed'])
            ->get()
            ->each(function ($report) use ($rule) {
                if ($rule->shouldEscalate($report)) {
                    $rule->execute($report);
                }
            });

        // Check gun applications
        GunApplication::whereNotIn('status', ['approved', 'rejected'])
            ->get()
            ->each(function ($application) use ($rule) {
                if ($rule->shouldEscalate($application)) {
                    $rule->execute($application);
                }
            });
    }

    /**
     * Check if application should be escalated based on rule
     */
    protected function shouldEscalate(GunApplication $application, EscalationRule $rule)
    {
        switch ($rule->trigger_condition) {
            case 'days_since_submission':
                $daysSinceSubmission = now()->diffInDays($application->submitted_at);
                return $daysSinceSubmission >= $rule->threshold_value;

            case 'days_since_last_update':
                $lastUpdate = $application->updated_at;
                $daysSinceUpdate = now()->diffInDays($lastUpdate);
                return $daysSinceUpdate >= $rule->threshold_value;

            case 'documents_pending':
                $pendingDocuments = $application->documents()
                    ->where('verification_status', 'pending')
                    ->count();
                return $pendingDocuments >= $rule->threshold_value;

            case 'high_priority_application':
                return $application->priority_level >= $rule->threshold_value;

            default:
                return false;
        }
    }

    /**
     * Escalate an application
     */
    public function escalateApplication(GunApplication $application, EscalationRule $rule, $escalatedBy = null)
    {
        try {
            // Update application status
            $application->update([
                'is_escalated' => true,
                'escalated_at' => now(),
                'escalated_by' => $escalatedBy,
                'priority_level' => max($application->priority_level, $rule->priority_level),
            ]);

            // Create escalation log
            $escalationLog = EscalationLog::create([
                'escalatable_type' => GunApplication::class,
                'escalatable_id' => $application->id,
                'escalation_rule_id' => $rule->id,
                'escalated_by' => $escalatedBy,
                'escalation_reason' => $this->generateEscalationReason($rule),
                'status' => 'pending',
            ]);

            // Notify escalation targets
            $this->notifyEscalationTargets($application, $rule, $escalationLog);

            // Notify the applicant
            $this->notifyApplicant($application, $escalationLog);

            Log::info("Application {$application->application_number} escalated using rule: {$rule->name}");

            return $escalationLog;

        } catch (\Exception $e) {
            Log::error("Failed to escalate application {$application->application_number}: " . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Notify escalation targets
     */
    protected function notifyEscalationTargets(GunApplication $application, EscalationRule $rule, EscalationLog $escalationLog)
    {
        $targets = $rule->escalation_targets;

        foreach ($targets as $target) {
            if (isset($target['type']) && $target['type'] === 'user_id') {
                $user = User::find($target['value']);
                if ($user) {
                    $escalationLog->update(['escalated_to' => $user->id]);
                    $this->notificationService->sendEscalationNotification($user, $application, $escalationLog);
                }
            } elseif (isset($target['type']) && $target['type'] === 'role') {
                $users = User::whereHas('roles', function ($query) use ($target) {
                    $query->where('name', $target['value']);
                })->get();

                foreach ($users as $user) {
                    $this->notificationService->sendEscalationNotification($user, $application, $escalationLog);
                }
            }
        }
    }

    /**
     * Notify applicant about escalation
     */
    protected function notifyApplicant(GunApplication $application, EscalationLog $escalationLog)
    {
        if ($application->user) {
            $this->notificationService->sendApplicantEscalationNotification(
                $application->user,
                $application,
                $escalationLog
            );
        }
    }

    /**
     * Generate escalation reason
     */
    protected function generateEscalationReason(EscalationRule $rule)
    {
        return "Application escalated due to: {$rule->description}";
    }

    /**
     * Acknowledge escalation
     */
    public function acknowledgeEscalation(EscalationLog $escalationLog, User $acknowledgedBy)
    {
        $escalationLog->update([
            'status' => 'acknowledged',
            'acknowledged_at' => now(),
        ]);

        Log::info("Escalation acknowledged by user: {$acknowledgedBy->id}");
    }

    /**
     * Resolve escalation
     */
    public function resolveEscalation(EscalationLog $escalationLog, User $resolvedBy, $resolutionNotes = null)
    {
        $escalationLog->update([
            'status' => 'resolved',
            'resolved_at' => now(),
            'resolution_notes' => $resolutionNotes,
        ]);

        // Update the application
        $application = $escalationLog->escalatable;
        if ($application) {
            $application->update(['is_escalated' => false]);
        }

        Log::info("Escalation resolved by user: {$resolvedBy->id}");
    }

    /**
     * Get escalation statistics
     */
    public function getEscalationStatistics($dateFrom = null, $dateTo = null)
    {
        $query = EscalationLog::query();

        if ($dateFrom) {
            $query->whereDate('created_at', '>=', $dateFrom);
        }

        if ($dateTo) {
            $query->whereDate('created_at', '<=', $dateTo);
        }

        return [
            'total_escalations' => $query->count(),
            'pending_escalations' => $query->where('status', 'pending')->count(),
            'acknowledged_escalations' => $query->where('status', 'acknowledged')->count(),
            'resolved_escalations' => $query->where('status', 'resolved')->count(),
            'average_resolution_time' => $this->calculateAverageResolutionTime($query),
        ];
    }

    /**
     * Calculate average resolution time
     */
    protected function calculateAverageResolutionTime($query)
    {
        $resolvedEscalations = $query->where('status', 'resolved')
            ->whereNotNull('resolved_at')
            ->get();

        if ($resolvedEscalations->isEmpty()) {
            return 0;
        }

        $totalMinutes = $resolvedEscalations->sum(function ($escalation) {
            return $escalation->created_at->diffInMinutes($escalation->resolved_at);
        });

        return round($totalMinutes / $resolvedEscalations->count(), 2);
    }
}