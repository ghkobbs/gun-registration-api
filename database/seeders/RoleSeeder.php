<?php
namespace Database\Seeders;

use App\Models\Role;
use Illuminate\Database\Seeder;

class RoleSeeder extends Seeder
{
    public function run()
    {
        $roles = [
            [
                'name' => 'super_admin',
                'display_name' => 'Super Administrator',
                'description' => 'Full system access with all permissions',
                'permissions' => [
                    'users.create', 'users.read', 'users.update', 'users.delete',
                    'roles.create', 'roles.read', 'roles.update', 'roles.delete',
                    'gun_applications.create', 'gun_applications.read', 'gun_applications.update', 'gun_applications.delete',
                    'gun_applications.approve', 'gun_applications.reject', 'gun_applications.escalate',
                    'gun_registrations.create', 'gun_registrations.read', 'gun_registrations.update', 'gun_registrations.delete',
                    'crime_reports.create', 'crime_reports.read', 'crime_reports.update', 'crime_reports.delete',
                    'crime_reports.assign', 'crime_reports.close',
                    'documents.verify', 'documents.reject',
                    'payments.view', 'payments.refund',
                    'notifications.send', 'notifications.bulk_send',
                    'reports.view', 'reports.export',
                    'system.settings', 'system.maintenance',
                ],
                'is_active' => true,
            ],
            [
                'name' => 'admin',
                'display_name' => 'Administrator',
                'description' => 'Administrative access with most permissions',
                'permissions' => [
                    'users.read', 'users.update',
                    'gun_applications.read', 'gun_applications.update', 'gun_applications.approve', 'gun_applications.reject',
                    'gun_registrations.read', 'gun_registrations.update',
                    'crime_reports.read', 'crime_reports.update', 'crime_reports.assign', 'crime_reports.close',
                    'documents.verify', 'documents.reject',
                    'payments.view',
                    'notifications.send',
                    'reports.view', 'reports.export',
                ],
                'is_active' => true,
            ],
            [
                'name' => 'staff',
                'display_name' => 'Staff Member',
                'description' => 'Staff with review and processing permissions',
                'permissions' => [
                    'gun_applications.read', 'gun_applications.update',
                    'gun_registrations.read',
                    'crime_reports.read', 'crime_reports.update',
                    'documents.verify',
                    'payments.view',
                    'reports.view',
                ],
                'is_active' => true,
            ],
            [
                'name' => 'law_enforcement',
                'display_name' => 'Law Enforcement',
                'description' => 'Police and law enforcement officers',
                'permissions' => [
                    'gun_applications.read',
                    'gun_registrations.read',
                    'crime_reports.read', 'crime_reports.update', 'crime_reports.assign', 'crime_reports.close',
                    'reports.view',
                ],
                'is_active' => true,
            ],
            [
                'name' => 'client',
                'display_name' => 'Client',
                'description' => 'Regular users who can submit applications and reports',
                'permissions' => [
                    'gun_applications.create', 'gun_applications.read_own', 'gun_applications.update_own',
                    'gun_registrations.read_own',
                    'crime_reports.create',
                    'documents.upload',
                    'payments.make',
                    'profile.update',
                ],
                'is_active' => true,
            ],
        ];

        foreach ($roles as $roleData) {
            Role::updateOrCreate(
                ['name' => $roleData['name']],
                $roleData
            );
        }
    }
}