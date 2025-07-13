<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Cache;

class NotificationTemplate extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'subject',
        'email_template',
        'sms_template',
        'variables',
        'type',
        'is_active',
    ];

    protected $casts = [
        'variables' => 'array',
        'is_active' => 'boolean',
    ];

    protected $attributes = [
        'type' => 'both',
        'is_active' => true,
    ];

    // Constants for template types
    const TYPE_EMAIL = 'email';
    const TYPE_SMS = 'sms';
    const TYPE_BOTH = 'both';

    // Available template types
    public static function getTypes()
    {
        return [
            self::TYPE_EMAIL => 'Email Only',
            self::TYPE_SMS => 'SMS Only',
            self::TYPE_BOTH => 'Both Email & SMS',
        ];
    }

    /**
     * Scope for active templates
     */
    public function scopeActive(Builder $query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope for email templates
     */
    public function scopeEmail(Builder $query)
    {
        return $query->whereIn('type', [self::TYPE_EMAIL, self::TYPE_BOTH]);
    }

    /**
     * Scope for SMS templates
     */
    public function scopeSms(Builder $query)
    {
        return $query->whereIn('type', [self::TYPE_SMS, self::TYPE_BOTH]);
    }

    /**
     * Scope to find template by name
     */
    public function scopeByName(Builder $query, $name)
    {
        return $query->where('name', $name);
    }

    /**
     * Get template by name with caching
     */
    public static function getByName($name)
    {
        return Cache::remember("notification_template_{$name}", 3600, function () use ($name) {
            return self::active()->where('name', $name)->first();
        });
    }

    /**
     * Render email template with variables
     */
    public function renderEmail($variables = [])
    {
        if (!in_array($this->type, [self::TYPE_EMAIL, self::TYPE_BOTH])) {
            return null;
        }

        return [
            'subject' => $this->replacePlaceholders($this->subject, $variables),
            'body' => $this->replacePlaceholders($this->email_template, $variables),
        ];
    }

    /**
     * Render SMS template with variables
     */
    public function renderSms($variables = [])
    {
        if (!in_array($this->type, [self::TYPE_SMS, self::TYPE_BOTH])) {
            return null;
        }

        return $this->replacePlaceholders($this->sms_template, $variables);
    }

    /**
     * Replace placeholders in template with actual values
     */
    public function replacePlaceholders($template, $variables = [])
    {
        foreach ($variables as $key => $value) {
            $template = str_replace('{{' . $key . '}}', $value, $template);
        }
        return $template;
    }

    /**
     * Get available placeholders for this template
     */
    public function getAvailablePlaceholders()
    {
        return $this->variables ?? [];
    }

    /**
     * Validate that all required placeholders are provided
     */
    public function validateVariables($providedVariables = [])
    {
        $requiredVariables = $this->getRequiredVariables();
        $missingVariables = array_diff($requiredVariables, array_keys($providedVariables));

        return [
            'valid' => empty($missingVariables),
            'missing' => $missingVariables,
        ];
    }

    /**
     * Extract required variables from template content
     */
    public function getRequiredVariables()
    {
        $emailPlaceholders = $this->extractPlaceholders($this->email_template);
        $smsPlaceholders = $this->extractPlaceholders($this->sms_template);
        $subjectPlaceholders = $this->extractPlaceholders($this->subject);

        return array_unique(array_merge($emailPlaceholders, $smsPlaceholders, $subjectPlaceholders));
    }

    /**
     * Extract placeholders from template string
     */
    private function extractPlaceholders($template)
    {
        if (!$template) {
            return [];
        }

        preg_match_all('/\{\{([^}]+)\}\}/', $template, $matches);
        return $matches[1] ?? [];
    }

    /**
     * Check if template supports email
     */
    public function supportsEmail()
    {
        return in_array($this->type, [self::TYPE_EMAIL, self::TYPE_BOTH]);
    }

    /**
     * Check if template supports SMS
     */
    public function supportsSms()
    {
        return in_array($this->type, [self::TYPE_SMS, self::TYPE_BOTH]);
    }

    /**
     * Activate template
     */
    public function activate()
    {
        $this->update(['is_active' => true]);
        $this->clearCache();
    }

    /**
     * Deactivate template
     */
    public function deactivate()
    {
        $this->update(['is_active' => false]);
        $this->clearCache();
    }

    /**
     * Clear template cache
     */
    public function clearCache()
    {
        Cache::forget("notification_template_{$this->name}");
    }

    /**
     * Boot method to clear cache on model events
     */
    protected static function boot()
    {
        parent::boot();

        static::saved(function ($template) {
            $template->clearCache();
        });

        static::deleted(function ($template) {
            $template->clearCache();
        });
    }

    /**
     * Get preview of rendered template
     */
    public function getPreview($sampleVariables = [])
    {
        // Use sample variables if none provided
        if (empty($sampleVariables)) {
            $sampleVariables = $this->getSampleVariables();
        }

        return [
            'email' => $this->renderEmail($sampleVariables),
            'sms' => $this->renderSms($sampleVariables),
        ];
    }

    /**
     * Get sample variables for preview
     */
    public function getSampleVariables()
    {
        $sampleData = [
            'user_name' => 'John Doe',
            'application_number' => 'APP-2024-001',
            'report_number' => 'CR-2024-001',
            'reference_code' => 'REF123456',
            'status' => 'Approved',
            'date' => now()->format('Y-m-d H:i:s'),
            'amount' => '150.00',
            'currency' => 'GHS',
            'payment_reference' => 'PAY-2024-001',
            'document_title' => 'National ID Card',
            'old_status' => 'Pending',
            'new_status' => 'Approved',
            'escalation_reason' => 'Processing delay',
            'priority_level' => 'High',
            'app_url' => config('app.url'),
            'support_email' => 'support@example.com',
        ];

        // Only return variables that are actually used in the template
        $requiredVariables = $this->getRequiredVariables();
        return array_intersect_key($sampleData, array_flip($requiredVariables));
    }

    /**
     * Create or update template
     */
    public static function createOrUpdate($name, $data)
    {
        return self::updateOrCreate(
            ['name' => $name],
            $data
        );
    }

    /**
     * Get all default templates
     */
    public static function getDefaultTemplates()
    {
        return [
            'application_status_update' => [
                'name' => 'application_status_update',
                'subject' => 'Application Status Update - {{application_number}}',
                'email_template' => '
                    <h2>Application Status Update</h2>
                    <p>Dear {{user_name}},</p>
                    <p>Your gun registration application <strong>{{application_number}}</strong> status has been updated.</p>
                    <ul>
                        <li><strong>Previous Status:</strong> {{old_status}}</li>
                        <li><strong>Current Status:</strong> {{new_status}}</li>
                        <li><strong>Date:</strong> {{date}}</li>
                    </ul>
                    <p>You can check your application status by logging into your account at <a href="{{app_url}}">{{app_url}}</a></p>
                    <p>Best regards,<br>Gun Registration System</p>
                ',
                'sms_template' => 'Application {{application_number}} status updated from {{old_status}} to {{new_status}}. Check your account for details.',
                'type' => 'both',
                'variables' => ['user_name', 'application_number', 'old_status', 'new_status', 'date', 'app_url'],
            ],
            
            'escalation_notification' => [
                'name' => 'escalation_notification',
                'subject' => 'Application Escalated - {{application_number}}',
                'email_template' => '
                    <h2>Application Escalated</h2>
                    <p>Dear {{user_name}},</p>
                    <p>Your application <strong>{{application_number}}</strong> has been escalated for priority processing.</p>
                    <p><strong>Reason:</strong> {{escalation_reason}}</p>
                    <p><strong>Priority Level:</strong> {{priority_level}}</p>
                    <p>We will process your application with priority and update you shortly.</p>
                    <p>Best regards,<br>Gun Registration System</p>
                ',
                'sms_template' => 'Your application {{application_number}} has been escalated for priority processing. Reason: {{escalation_reason}}',
                'type' => 'both',
                'variables' => ['user_name', 'application_number', 'escalation_reason', 'priority_level'],
            ],
            
            'crime_report_update' => [
                'name' => 'crime_report_update',
                'subject' => 'Crime Report Update - {{report_number}}',
                'email_template' => '
                    <h2>Crime Report Update</h2>
                    <p>Dear {{reporter_name}},</p>
                    <p>Your crime report <strong>{{report_number}}</strong> has been updated.</p>
                    <p><strong>Reference Code:</strong> {{reference_code}}</p>
                    <p><strong>Status:</strong> {{status}}</p>
                    <p><strong>Update:</strong> {{update_message}}</p>
                    <p>Thank you for helping keep our community safe.</p>
                ',
                'sms_template' => 'Crime report {{report_number}} updated. Status: {{status}}. Ref: {{reference_code}}',
                'type' => 'both',
                'variables' => ['reporter_name', 'report_number', 'reference_code', 'status', 'update_message'],
            ],
            
            'payment_confirmation' => [
                'name' => 'payment_confirmation',
                'subject' => 'Payment Confirmation - {{payment_reference}}',
                'email_template' => '
                    <h2>Payment Confirmation</h2>
                    <p>Dear {{user_name}},</p>
                    <p>Your payment has been successfully processed.</p>
                    <ul>
                        <li><strong>Payment Reference:</strong> {{payment_reference}}</li>
                        <li><strong>Amount:</strong> {{currency}} {{amount}}</li>
                        <li><strong>Description:</strong> {{description}}</li>
                        <li><strong>Date:</strong> {{date}}</li>
                    </ul>
                    <p>Thank you for your payment.</p>
                ',
                'sms_template' => 'Payment confirmed. Ref: {{payment_reference}}, Amount: {{currency}} {{amount}}. Thank you!',
                'type' => 'both',
                'variables' => ['user_name', 'payment_reference', 'amount', 'currency', 'description', 'date'],
            ],
        ];
    }

    /**
     * Seed default templates
     */
    public static function seedDefaults()
    {
        $templates = self::getDefaultTemplates();
        
        foreach ($templates as $template) {
            self::createOrUpdate($template['name'], $template);
        }
    }
}