<?php
// database/seeders/UserSeeder.php

namespace Database\Seeders;

use App\Models\User;
use App\Models\Role;
use App\Models\UserProfile;
use App\Models\UserAddress;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    public function run()
    {
        // Create Super Admin
        $superAdmin = User::updateOrCreate(
            ['email' => 'admin@guncrimeapi.com'],
            [
                'first_name' => 'Super',
                'last_name' => 'Administrator',
                'phone_number' => '+233200000001',
                'national_id' => 'GHA-000000001-1',
                'password' => Hash::make('password'),
                'user_type' => 'admin',
                'status' => 'active',
                'email_verified_at' => now(),
                'phone_verified_at' => now(),
                'national_id_verified_at' => now(),
                'national_id_status' => 'verified',
            ]
        );

        // Assign Super Admin Role
        $superAdminRole = Role::where('name', 'super_admin')->first();
        if ($superAdminRole) {
            $superAdmin->roles()->syncWithoutDetaching([$superAdminRole->id]);
        }

        // Create Profile for Super Admin
        UserProfile::updateOrCreate(
            ['user_id' => $superAdmin->id],
            [
                'date_of_birth' => '1980-01-01',
                'gender' => 'male',
                'occupation' => 'System Administrator',
            ]
        );

        // Create Address for Super Admin
        UserAddress::updateOrCreate(
            ['user_id' => $superAdmin->id, 'type' => 'residential'],
            [
                'street_address' => 'Airport Residential Area',
                'city' => 'Accra',
                'region' => 'Greater Accra Region',
                'country' => 'Ghana',
                'is_primary' => true,
            ]
        );

        // Create Admin User
        $admin = User::updateOrCreate(
            ['email' => 'manager@guncrimeapi.com'],
            [
                'first_name' => 'John',
                'last_name' => 'Manager',
                'phone_number' => '+233200000002',
                'national_id' => 'GHA-000000002-2',
                'password' => Hash::make('password'),
                'user_type' => 'admin',
                'status' => 'active',
                'email_verified_at' => now(),
                'phone_verified_at' => now(),
                'national_id_verified_at' => now(),
                'national_id_status' => 'verified',
            ]
        );

        $adminRole = Role::where('name', 'admin')->first();
        if ($adminRole) {
            $admin->roles()->syncWithoutDetaching([$adminRole->id]);
        }

        // Create Staff User
        $staff = User::updateOrCreate(
            ['email' => 'staff@guncrimeapi.com'],
            [
                'first_name' => 'Jane',
                'last_name' => 'Staff',
                'phone_number' => '+233200000003',
                'national_id' => 'GHA-000000003-3',
                'password' => Hash::make('password'),
                'user_type' => 'staff',
                'status' => 'active',
                'email_verified_at' => now(),
                'phone_verified_at' => now(),
                'national_id_verified_at' => now(),
                'national_id_status' => 'verified',
            ]
        );

        $staffRole = Role::where('name', 'staff')->first();
        if ($staffRole) {
            $staff->roles()->syncWithoutDetaching([$staffRole->id]);
        }

        // Create Law Enforcement User
        $lawEnforcement = User::updateOrCreate(
            ['email' => 'police@guncrimeapi.com'],
            [
                'first_name' => 'Officer',
                'last_name' => 'Police',
                'phone_number' => '+233200000004',
                'national_id' => 'GHA-000000004-4',
                'password' => Hash::make('password'),
                'user_type' => 'law_enforcement',
                'status' => 'active',
                'email_verified_at' => now(),
                'phone_verified_at' => now(),
                'national_id_verified_at' => now(),
                'national_id_status' => 'verified',
            ]
        );

        $lawRole = Role::where('name', 'law_enforcement')->first();
        if ($lawRole) {
            $lawEnforcement->roles()->syncWithoutDetaching([$lawRole->id]);
        }

        // Create Sample Client
        $client = User::updateOrCreate(
            ['email' => 'client@example.com'],
            [
                'first_name' => 'Michael',
                'last_name' => 'Client',
                'phone_number' => '+233200000005',
                'national_id' => 'GHA-000000005-5',
                'password' => Hash::make('password'),
                'user_type' => 'client',
                'status' => 'active',
                'email_verified_at' => now(),
                'phone_verified_at' => now(),
                'national_id_verified_at' => now(),
                'national_id_status' => 'verified',
            ]
        );

        $clientRole = Role::where('name', 'client')->first();
        if ($clientRole) {
            $client->roles()->syncWithoutDetaching([$clientRole->id]);
        }

        // Create profiles and addresses for other users
        foreach ([$admin, $staff, $lawEnforcement, $client] as $user) {
            UserProfile::updateOrCreate(
                ['user_id' => $user->id],
                [
                    'date_of_birth' => '1985-05-15',
                    'gender' => 'male',
                    'occupation' => 'Professional',
                ]
            );

            UserAddress::updateOrCreate(
                ['user_id' => $user->id, 'type' => 'residential'],
                [
                    'street_address' => 'Sample Address',
                    'city' => 'Accra',
                    'region' => 'Greater Accra Region',
                    'country' => 'Ghana',
                    'is_primary' => true,
                ]
            );
        }

        $this->command->info('Default users created with email/password:');
        $this->command->info('admin@guncrimeapi.com / password (Super Admin)');
        $this->command->info('manager@guncrimeapi.com / password (Admin)');
        $this->command->info('staff@guncrimeapi.com / password (Staff)');
        $this->command->info('police@guncrimeapi.com / password (Law Enforcement)');
        $this->command->info('client@example.com / password (Client)');
    }
}