<?php
namespace App\Models;

use App\Notifications\EscalationNotification;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class EscalationRule extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'description',
        'trigger_condition',
        'threshold_value',
        'escalation_action',
        'escalation_targets',
        'priority_level',
        'is_active',
    ];

    protected $casts = [
        'escalation_targets' => 'array',
        'threshold_value' => 'integer',
        'priority_level' => 'integer',
        'is_active' => 'boolean',
    ];

    // Relationships
    public function escalationLogs(): HasMany
    {
        return $this->hasMany(EscalationLog::class);
    }

    // Methods
    public function shouldEscalate($model): bool
    {
        if (!$this->is_active) {
            return false;
        }

        switch ($this->trigger_condition) {
            case 'days_since_submission':
                return $this->checkDaysSinceSubmission($model);
            case 'days_since_last_update':
                return $this->checkDaysSinceLastUpdate($model);
            case 'status_unchanged':
                return $this->checkStatusUnchanged($model);
            default:
                return false;
        }
    }

    private function checkDaysSinceSubmission($model): bool
    {
        if (!isset($model->submitted_at)) {
            return false;
        }

        $daysSinceSubmission = now()->diffInDays($model->submitted_at);
        return $daysSinceSubmission >= $this->threshold_value;
    }

    private function checkDaysSinceLastUpdate($model): bool
    {
        $daysSinceUpdate = now()->diffInDays($model->updated_at);
        return $daysSinceUpdate >= $this->threshold_value;
    }

    private function checkStatusUnchanged($model): bool
    {
        if (!isset($model->status)) {
            return false;
        }

        // Check if status has been unchanged for threshold days
        $lastStatusChange = AuditLog::where('auditable_type', get_class($model))
            ->where('auditable_id', $model->id)
            ->where('event', 'status_changed')
            ->latest()
            ->first();

        if (!$lastStatusChange) {
            return $this->checkDaysSinceSubmission($model);
        }

        $daysSinceStatusChange = now()->diffInDays($lastStatusChange->created_at);
        return $daysSinceStatusChange >= $this->threshold_value;
    }

    public function getEscalationTargets(): array
    {
        $targets = [];
        
        foreach ($this->escalation_targets as $target) {
            if (is_numeric($target)) {
                // It's a user ID
                $user = User::find($target);
                if ($user) {
                    $targets[] = $user;
                }
            } else {
                // It's a role name
                $users = User::whereHas('roles', function ($query) use ($target) {
                    $query->where('name', $target);
                })->get();
                
                $targets = array_merge($targets, $users->toArray());
            }
        }
        
        return $targets;
    }

    public function execute($model): EscalationLog
    {
        $escalationLog = EscalationLog::create([
            'escalatable_type' => get_class($model),
            'escalatable_id' => $model->id,
            'escalation_rule_id' => $this->id,
            'escalation_reason' => $this->generateEscalationReason($model),
            'status' => 'pending',
        ]);

        // Notify escalation targets
        $this->notifyEscalationTargets($model, $escalationLog);

        return $escalationLog;
    }

    private function generateEscalationReason($model): string
    {
        switch ($this->trigger_condition) {
            case 'days_since_submission':
                return "No action taken for {$this->threshold_value} days since submission";
            case 'days_since_last_update':
                return "No updates for {$this->threshold_value} days";
            case 'status_unchanged':
                return "Status unchanged for {$this->threshold_value} days";
            default:
                return "Escalation triggered by rule: {$this->name}";
        }
    }

    private function notifyEscalationTargets($model, EscalationLog $escalationLog): void
    {
        $targets = $this->getEscalationTargets();
        
        foreach ($targets as $target) {
            // Send notification to each target
            $target->notify(new EscalationNotification($model, $escalationLog));
        }
    }
}