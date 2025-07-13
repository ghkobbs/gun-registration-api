<?php

namespace Database\Seeders;

use App\Models\User;
// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $this->call([
						UserSeeder::class,
						RoleSeeder::class,
						RegionSeeder::class,
						DistrictSeeder::class,
						CommunitySeeder::class,
						CrimeCategorySeeder::class,
						NotificationTemplateSeeder::class,
						CrimeTypeSeeder::class,
						SystemSettingSeeder::class,
						EscalationRuleSeeder::class,
				]);
    }
}
