<?php
namespace Database\Seeders;

use App\Models\Region;
use Illuminate\Database\Seeder;

class RegionSeeder extends Seeder
{
    public function run()
    {
        $regions = [
            ['name' => 'Greater Accra Region', 'code' => 'GAR'],
            ['name' => 'Ashanti Region', 'code' => 'AR'],
            ['name' => 'Western Region', 'code' => 'WR'],
            ['name' => 'Western North Region', 'code' => 'WNR'],
            ['name' => 'Central Region', 'code' => 'CR'],
            ['name' => 'Eastern Region', 'code' => 'ER'],
            ['name' => 'Volta Region', 'code' => 'VR'],
            ['name' => 'Oti Region', 'code' => 'OR'],
            ['name' => 'Northern Region', 'code' => 'NR'],
            ['name' => 'North East Region', 'code' => 'NER'],
            ['name' => 'Savannah Region', 'code' => 'SR'],
            ['name' => 'Upper East Region', 'code' => 'UER'],
            ['name' => 'Upper West Region', 'code' => 'UWR'],
            ['name' => 'Bono Region', 'code' => 'BR'],
            ['name' => 'Bono East Region', 'code' => 'BER'],
            ['name' => 'Ahafo Region', 'code' => 'AHR'],
        ];

        foreach ($regions as $region) {
            Region::updateOrCreate(
                ['code' => $region['code']],
                $region
            );
        }
    }
}