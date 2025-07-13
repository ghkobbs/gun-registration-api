<?php
namespace Database\Seeders;

use App\Models\Region;
use App\Models\District;
use Illuminate\Database\Seeder;

class DistrictSeeder extends Seeder
{
    public function run()
    {
        $districts = [
            // Greater Accra Region
            'GAR' => [
                ['name' => 'Accra Metropolitan', 'code' => 'AMA'],
                ['name' => 'Tema Metropolitan', 'code' => 'TMA'],
                ['name' => 'Ga West Municipal', 'code' => 'GWMA'],
                ['name' => 'Ga East Municipal', 'code' => 'GEMA'],
                ['name' => 'Ga South Municipal', 'code' => 'GSMA'],
                ['name' => 'Ga Central Municipal', 'code' => 'GCMA'],
                ['name' => 'Ledzokuku Municipal', 'code' => 'LMA'],
                ['name' => 'Krowor Municipal', 'code' => 'KMA'],
                ['name' => 'Adentan Municipal', 'code' => 'ADMA'],
                ['name' => 'Ashaiman Municipal', 'code' => 'ASHMA'],
            ],
            
            // Ashanti Region
            'AR' => [
                ['name' => 'Kumasi Metropolitan', 'code' => 'KMA'],
                ['name' => 'Obuasi Municipal', 'code' => 'OMA'],
                ['name' => 'Ejisu Municipal', 'code' => 'EMA'],
                ['name' => 'Juaben Municipal', 'code' => 'JMA'],
                ['name' => 'Kwabre East Municipal', 'code' => 'KEMA'],
                ['name' => 'Atwima Nwabiagya Municipal', 'code' => 'ANMA'],
                ['name' => 'Afigya Kwabre South District', 'code' => 'AKSDA'],
                ['name' => 'Bosomtwe District', 'code' => 'BDA'],
            ],
            
            // Northern Region
            'NR' => [
                ['name' => 'Tamale Metropolitan', 'code' => 'TAMA'],
                ['name' => 'Sagnarigu Municipal', 'code' => 'SAMA'],
                ['name' => 'Tolon District', 'code' => 'TDA'],
                ['name' => 'Kumbungu District', 'code' => 'KUDA'],
                ['name' => 'Savelugu Municipal', 'code' => 'SAVMA'],
                ['name' => 'Nanton District', 'code' => 'NANDA'],
            ],
            
            // Western Region
            'WR' => [
                ['name' => 'Sekondi-Takoradi Metropolitan', 'code' => 'STMA'],
                ['name' => 'Shama District', 'code' => 'SHADA'],
                ['name' => 'Ahanta West District', 'code' => 'AWDA'],
                ['name' => 'Nzema East Municipal', 'code' => 'NEMA'],
                ['name' => 'Ellembelle District', 'code' => 'EDA'],
            ],
            
            // Central Region
            'CR' => [
                ['name' => 'Cape Coast Metropolitan', 'code' => 'CCMA'],
                ['name' => 'Komenda-Edina-Eguafo-Abreim Municipal', 'code' => 'KEEA'],
                ['name' => 'Abura-Asebu-Kwamankese District', 'code' => 'AAKDA'],
                ['name' => 'Mfantsiman Municipal', 'code' => 'MMA'],
                ['name' => 'Gomoa Central District', 'code' => 'GCDA'],
            ],
        ];

        foreach ($districts as $regionCode => $regionDistricts) {
            $region = Region::where('code', $regionCode)->first();
            if ($region) {
                foreach ($regionDistricts as $district) {
                    District::updateOrCreate(
                        ['code' => $district['code']],
                        array_merge($district, ['region_id' => $region->id])
                    );
                }
            }
        }
    }
}