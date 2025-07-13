<?php

namespace Database\Seeders;

use App\Models\SystemSettings;
use Illuminate\Database\Seeder;

class SystemSettingSeeder extends Seeder
{
    public function run()
    {
        $settings = [
            // Application Settings
            [
                'key' => 'app.name',
                'value' => 'Gun Crime Reporting System',
                'type' => 'string',
                'description' => 'Application name',
                'category' => 'application',
                'is_public' => true,
            ],
            [
                'key' => 'app.description',
                'value' => 'Integrated system for gun registration and crime reporting in Ghana',
                'type' => 'string',
                'description' => 'Application description',
                'category' => 'application',
                'is_public' => true,
            ],
            
            // Gun Registration Settings
            [
                'key' => 'gun_registration.processing_time_days',
                'value' => '14',
                'type' => 'integer',
                'description' => 'Standard processing time for gun applications in days',
                'category' => 'gun_registration',
                'is_public' => false,
            ],
            [
                'key' => 'gun_registration.escalation_threshold_days',
                'value' => '7',
                'type' => 'integer',
                'description' => 'Days before application is escalated',
                'category' => 'gun_registration',
                'is_public' => false,
            ],
            [
                'key' => 'gun_registration.license_validity_years',
                'value' => '3',
                'type' => 'integer',
                'description' => 'Gun license validity period in years',
                'category' => 'gun_registration',
                'is_public' => true,
            ],
            [
                'key' => 'gun_registration.registration_fee',
                'value' => '200.00',
                'type' => 'string',
                'description' => 'Gun registration fee in GHS',
                'category' => 'gun_registration',
                'is_public' => true,
            ],
            [
                'key' => 'gun_registration.renewal_fee',
                'value' => '150.00',
                'type' => 'string',
                'description' => 'Gun license renewal fee in GHS',
                'category' => 'gun_registration',
                'is_public' => true,
            ],
            
            // Crime Reporting Settings
            [
                'key' => 'crime_reporting.ussd_code',
                'value' => '*920#',
                'type' => 'string',
                'description' => 'USSD code for crime reporting',
                'category' => 'crime_reporting',
                'is_public' => true,
            ],
            [
                'key' => 'crime_reporting.emergency_number',
                'value' => '191',
                'type' => 'string',
                'description' => 'Emergency contact number',
                'category' => 'crime_reporting',
                'is_public' => true,
            ],
            [
                'key' => 'crime_reporting.anonymous_reports_enabled',
                'value' => 'true',
                'type' => 'boolean',
                'description' => 'Allow anonymous crime reports',
                'category' => 'crime_reporting',
                'is_public' => true,
            ],
            
            // File Upload Settings
            [
                'key' => 'files.max_upload_size_mb',
                'value' => '10',
                'type' => 'integer',
                'description' => 'Maximum file upload size in MB',
                'category' => 'files',
                'is_public' => false,
            ],
            [
                'key' => 'files.allowed_document_types',
                'value' => json_encode(['pdf', 'jpg', 'jpeg', 'png', 'doc', 'docx']),
                'type' => 'json',
                'description' => 'Allowed document file types',
                'category' => 'files',
                'is_public' => false,
            ],
            [
                'key' => 'files.allowed_evidence_types',
                'value' => json_encode(['jpg', 'jpeg', 'png', 'mp4', 'mov', 'pdf']),
                'type' => 'json',
                'description' => 'Allowed evidence file types',
                'category' => 'files',
                'is_public' => false,
            ],
            
            // Notification Settings
            [
                'key' => 'notifications.email_enabled',
                'value' => 'true',
                'type' => 'boolean',
                'description' => 'Enable email notifications',
                'category' => 'notifications',
                'is_public' => false,
            ],
            [
                'key' => 'notifications.sms_enabled',
                'value' => 'true',
                'type' => 'boolean',
                'description' => 'Enable SMS notifications',
                'category' => 'notifications',
                'is_public' => false,
            ],
            [
                'key' => 'notifications.expiry_reminder_days',
                'value' => json_encode([30, 14, 7, 1]),
                'type' => 'json',
                'description' => 'Days before expiry to send reminders',
                'category' => 'notifications',
                'is_public' => false,
            ],
            
            // Contact Information
            [
                'key' => 'contact.support_email',
                'value' => 'support@guncrimeapi.com',
                'type' => 'string',
                'description' => 'Support email address',
                'category' => 'contact',
                'is_public' => true,
            ],
            [
                'key' => 'contact.support_phone',
                'value' => '+233200000000',
                'type' => 'string',
                'description' => 'Support phone number',
                'category' => 'contact',
                'is_public' => true,
            ],
            [
                'key' => 'contact.office_address',
                'value' => 'Ministry of Interior, Accra, Ghana',
                'type' => 'string',
                'description' => 'Office address',
                'category' => 'contact',
                'is_public' => true,
            ],
        ];

        foreach ($settings as $setting) {
            SystemSettings::updateOrCreate(
                ['key' => $setting['key']],
                $setting
            );
        }
    }
}