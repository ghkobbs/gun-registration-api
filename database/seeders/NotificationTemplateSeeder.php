<?php
namespace Database\Seeders;

use App\Models\NotificationTemplate;
use Illuminate\Database\Seeder;

class NotificationTemplateSeeder extends Seeder
{
    public function run()
    {
        // Seed default templates
        NotificationTemplate::seedDefaults();
        
        // You can add more specific templates here if needed
        $additionalTemplates = [
            [
                'name' => 'document_verification',
                'subject' => 'Document Verification - {{document_title}}',
                'email_template' => '
                    <h2>Document Verification Update</h2>
                    <p>Dear {{user_name}},</p>
                    <p>Your document <strong>{{document_title}}</strong> has been {{status}}.</p>
                    <p><strong>Notes:</strong> {{notes}}</p>
                    <p><strong>Next Steps:</strong> {{next_steps}}</p>
                    <p>Date: {{date}}</p>
                ',
                'sms_template' => 'Document {{document_title}} {{status}}. {{notes}}',
                'type' => 'both',
                'variables' => ['user_name', 'document_title', 'status', 'notes', 'next_steps', 'date'],
                'is_active' => true,
            ],
            
            [
                'name' => 'license_expiry_reminder',
                'subject' => 'License Expiry Reminder - {{registration_number}}',
                'email_template' => '
                    <h2>License Expiry Reminder</h2>
                    <p>Dear {{user_name}},</p>
                    <p>Your firearm license <strong>{{registration_number}}</strong> will expire in {{days_until_expiry}} days.</p>
                    <p><strong>Firearm Type:</strong> {{firearm_type}}</p>
                    <p><strong>Expiry Date:</strong> {{expiry_date}}</p>
                    <p>Please renew your license before it expires to avoid any penalties.</p>
                    <p><a href="{{renewal_url}}">Renew Now</a></p>
                ',
                'sms_template' => 'License {{registration_number}} expires in {{days_until_expiry}} days. Renew at {{renewal_url}}',
                'type' => 'both',
                'variables' => ['user_name', 'registration_number', 'firearm_type', 'expiry_date', 'days_until_expiry', 'renewal_url'],
                'is_active' => true,
            ],
        ];
        
        foreach ($additionalTemplates as $template) {
            NotificationTemplate::createOrUpdate($template['name'], $template);
        }
    }
}