<?php
// database/seeders/CommunitySeeder.php

namespace Database\Seeders;

use App\Models\District;
use App\Models\Community;
use Illuminate\Database\Seeder;

class CommunitySeeder extends Seeder
{
    public function run()
    {
        $communities = [
            // Accra Metropolitan
            'AMA' => [
                ['name' => 'Osu', 'code' => 'OSU', 'latitude' => 5.5502, 'longitude' => -0.1735],
                ['name' => 'Labone', 'code' => 'LAB', 'latitude' => 5.5641, 'longitude' => -0.1661],
                ['name' => 'Airport Residential', 'code' => 'AIR', 'latitude' => 5.6037, 'longitude' => -0.1870],
                ['name' => 'Adabraka', 'code' => 'ADA', 'latitude' => 5.5569, 'longitude' => -0.2157],
                ['name' => 'Asylum Down', 'code' => 'ASY', 'latitude' => 5.5601, 'longitude' => -0.2065],
                ['name' => 'Dzorwulu', 'code' => 'DZO', 'latitude' => 5.5910, 'longitude' => -0.1958],
                ['name' => 'East Legon', 'code' => 'ELE', 'latitude' => 5.6508, 'longitude' => -0.1467],
                ['name' => 'Cantonments', 'code' => 'CAN', 'latitude' => 5.5713, 'longitude' => -0.1737],
            ],
            
            // Kumasi Metropolitan
            'KMA' => [
                ['name' => 'Bantama', 'code' => 'BAN', 'latitude' => 6.7153, 'longitude' => -1.6315],
                ['name' => 'Asokwa', 'code' => 'ASO', 'latitude' => 6.6885, 'longitude' => -1.6475],
                ['name' => 'Subin', 'code' => 'SUB', 'latitude' => 6.6971, 'longitude' => -1.6142],
                ['name' => 'Manhyia', 'code' => 'MAN', 'latitude' => 6.7089, 'longitude' => -1.6204],
                ['name' => 'Nhyiaeso', 'code' => 'NHY', 'latitude' => 6.6736, 'longitude' => -1.5889],
                ['name' => 'Adum', 'code' => 'ADU', 'latitude' => 6.6885, 'longitude' => -1.6238],
            ],
            
            // Tamale Metropolitan
            'TAMA' => [
                ['name' => 'Tamale Central', 'code' => 'TAC', 'latitude' => 9.4034, 'longitude' => -0.8424],
                ['name' => 'Kalpohin', 'code' => 'KAL', 'latitude' => 9.3976, 'longitude' => -0.8312],
                ['name' => 'Changli', 'code' => 'CHA', 'latitude' => 9.4187, 'longitude' => -0.8654],
                ['name' => 'Vittin', 'code' => 'VIT', 'latitude' => 9.4267, 'longitude' => -0.8234],
            ],
            
            // Sekondi-Takoradi Metropolitan
            'STMA' => [
                ['name' => 'Sekondi', 'code' => 'SEK', 'latitude' => 4.9352, 'longitude' => -1.7093],
                ['name' => 'Takoradi', 'code' => 'TAK', 'latitude' => 4.8845, 'longitude' => -1.7554],
                ['name' => 'Essikado', 'code' => 'ESS', 'latitude' => 4.9423, 'longitude' => -1.7234],
                ['name' => 'Ketan', 'code' => 'KET', 'latitude' => 4.8956, 'longitude' => -1.7423],
            ],
        ];

        foreach ($communities as $districtCode => $districtCommunities) {
            $district = District::where('code', $districtCode)->first();
            if ($district) {
                foreach ($districtCommunities as $community) {
                    Community::updateOrCreate(
                        ['code' => $community['code']],
                        array_merge($community, ['district_id' => $district->id])
                    );
                }
            }
        }
    }
}