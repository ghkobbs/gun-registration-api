<?php
namespace Database\Seeders;

use App\Models\EscalationRule;
use Illuminate\Database\Seeder;

class EscalationRuleSeeder extends Seeder
{
    public function run()
    {
        $rules = [
            [
                'name' => 'Application Processing Delay',
                'description' => 'Escalate gun applications that have been pending for more than 7 days',
                'trigger_condition' => 'days_since_submission',
                'threshold_value' => 7,
                'escalation_action' => 'notify_supervisor',
                'escalation_targets' => json_encode(['role:admin', 'role:super_admin']),
                'priority_level' => 2,
                'is_active' => true,
            ],
            [
                'name' => 'Critical Application Delay',
                'description' => 'Escalate gun applications that have been pending for more than 14 days',
                'trigger_condition' => 'days_since_submission',
                'threshold_value' => 14,
                'escalation_action' => 'escalate_to_management',
                'escalation_targets' => json_encode(['role:super_admin']),
                'priority_level' => 4,
                'is_active' => true,
            ],
            [
                'name' => 'Document Verification Delay',
                'description' => 'Escalate documents pending verification for more than 3 days',
                'trigger_condition' => 'days_since_document_upload',
                'threshold_value' => 3,
                'escalation_action' => 'notify_verification_team',
                'escalation_targets' => json_encode(['role:staff', 'role:admin']),
                'priority_level' => 2,
                'is_active' => true,
            ],
            [
                'name' => 'Crime Report Assignment Delay',
                'description' => 'Escalate unassigned crime reports after 24 hours',
                'trigger_condition' => 'hours_since_submission',
                'threshold_value' => 24,
                'escalation_action' => 'notify_law_enforcement',
                'escalation_targets' => json_encode(['role:law_enforcement', 'role:admin']),
                'priority_level' => 3,
                'is_active' => true,
            ],
            [
                'name' => 'High Priority Crime Report',
                'description' => 'Immediately escalate high priority crime reports',
                'trigger_condition' => 'crime_severity_level',
                'threshold_value' => 4,
                'escalation_action' => 'immediate_notification',
                'escalation_targets' => json_encode(['role:law_enforcement', 'role:admin', 'role:super_admin']),
                'priority_level' => 4,
                'is_active' => true,
            ],
            [
                'name' => 'Payment Processing Delay',
                'description' => 'Escalate failed payment processing after 2 hours',
                'trigger_condition' => 'hours_since_payment_failure',
                'threshold_value' => 2,
                'escalation_action' => 'notify_finance_team',
                'escalation_targets' => json_encode(['role:admin']),
                'priority_level' => 2,
                'is_active' => true,
            ],
        ];

        foreach ($rules as $rule) {
            EscalationRule::updateOrCreate(
                ['name' => $rule['name']],
                $rule
            );
        }
    }
}