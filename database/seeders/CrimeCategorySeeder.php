<?php
namespace Database\Seeders;

use App\Models\CrimeCategory;
use Illuminate\Database\Seeder;

class CrimeCategorySeeder extends Seeder
{
    public function run()
    {
        $categories = [
            [
                'name' => 'Violent Crimes',
                'description' => 'Crimes involving force or threat of force against persons',
                'icon' => 'fas fa-fist-raised',
                'color' => '#dc3545',
                'priority_level' => 4,
            ],
            [
                'name' => 'Property Crimes',
                'description' => 'Crimes involving theft or destruction of property',
                'icon' => 'fas fa-home',
                'color' => '#fd7e14',
                'priority_level' => 3,
            ],
            [
                'name' => 'Drug-Related Crimes',
                'description' => 'Crimes involving illegal drugs and substances',
                'icon' => 'fas fa-pills',
                'color' => '#6f42c1',
                'priority_level' => 3,
            ],
            [
                'name' => 'Cybercrime',
                'description' => 'Crimes committed using computers or the internet',
                'icon' => 'fas fa-laptop',
                'color' => '#0dcaf0',
                'priority_level' => 2,
            ],
            [
                'name' => 'Traffic Violations',
                'description' => 'Violations of traffic laws and regulations',
                'icon' => 'fas fa-car-crash',
                'color' => '#ffc107',
                'priority_level' => 2,
            ],
            [
                'name' => 'White Collar Crimes',
                'description' => 'Non-violent crimes committed for financial gain',
                'icon' => 'fas fa-briefcase',
                'color' => '#6c757d',
                'priority_level' => 2,
            ],
            [
                'name' => 'Public Order',
                'description' => 'Crimes against public peace and order',
                'icon' => 'fas fa-bullhorn',
                'color' => '#198754',
                'priority_level' => 1,
            ],
            [
                'name' => 'Environmental Crimes',
                'description' => 'Crimes against the environment',
                'icon' => 'fas fa-leaf',
                'color' => '#20c997',
                'priority_level' => 1,
            ],
        ];

        foreach ($categories as $category) {
            CrimeCategory::updateOrCreate(
                ['name' => $category['name']],
                $category
            );
        }
    }
}