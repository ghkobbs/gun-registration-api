<?php
namespace Database\Seeders;

use App\Models\CrimeCategory;
use App\Models\CrimeType;
use Illuminate\Database\Seeder;

class CrimeTypeSeeder extends Seeder
{
    public function run()
    {
        $crimeTypes = [
            'Violent Crimes' => [
                ['name' => 'Murder', 'description' => 'Unlawful killing of another person', 'severity_level' => 4, 'requires_immediate_response' => true],
                ['name' => 'Assault', 'description' => 'Physical attack on another person', 'severity_level' => 3, 'requires_immediate_response' => true],
                ['name' => 'Armed Robbery', 'description' => 'Theft using weapons or force', 'severity_level' => 4, 'requires_immediate_response' => true],
                ['name' => 'Rape', 'description' => 'Sexual assault', 'severity_level' => 4, 'requires_immediate_response' => true],
                ['name' => 'Kidnapping', 'description' => 'Unlawful detention of a person', 'severity_level' => 4, 'requires_immediate_response' => true],
                ['name' => 'Domestic Violence', 'description' => 'Violence within household', 'severity_level' => 3, 'requires_immediate_response' => true],
            ],
            
            'Property Crimes' => [
                ['name' => 'Burglary', 'description' => 'Breaking into buildings to steal', 'severity_level' => 3, 'requires_immediate_response' => false],
                ['name' => 'Theft', 'description' => 'Stealing property without force', 'severity_level' => 2, 'requires_immediate_response' => false],
                ['name' => 'Motor Vehicle Theft', 'description' => 'Stealing vehicles', 'severity_level' => 3, 'requires_immediate_response' => false],
                ['name' => 'Vandalism', 'description' => 'Destruction of property', 'severity_level' => 2, 'requires_immediate_response' => false],
                ['name' => 'Arson', 'description' => 'Intentionally setting fires', 'severity_level' => 4, 'requires_immediate_response' => true],
                ['name' => 'Pickpocketing', 'description' => 'Stealing from person without notice', 'severity_level' => 2, 'requires_immediate_response' => false],
            ],
            
            'Drug-Related Crimes' => [
                ['name' => 'Drug Trafficking', 'description' => 'Selling or distributing illegal drugs', 'severity_level' => 3, 'requires_immediate_response' => false],
                ['name' => 'Drug Possession', 'description' => 'Possessing illegal drugs', 'severity_level' => 2, 'requires_immediate_response' => false],
                ['name' => 'Drug Manufacturing', 'description' => 'Producing illegal drugs', 'severity_level' => 3, 'requires_immediate_response' => false],
                ['name' => 'Drug Use in Public', 'description' => 'Using drugs in public places', 'severity_level' => 1, 'requires_immediate_response' => false],
            ],
            
            'Cybercrime' => [
                ['name' => 'Online Fraud', 'description' => 'Internet-based financial fraud', 'severity_level' => 2, 'requires_immediate_response' => false],
                ['name' => 'Identity Theft', 'description' => 'Stealing personal information', 'severity_level' => 2, 'requires_immediate_response' => false],
                ['name' => 'Cyberbullying', 'description' => 'Online harassment', 'severity_level' => 2, 'requires_immediate_response' => false],
                ['name' => 'Hacking', 'description' => 'Unauthorized access to systems', 'severity_level' => 3, 'requires_immediate_response' => false],
                ['name' => 'Phishing', 'description' => 'Deceptive attempts to steal information', 'severity_level' => 2, 'requires_immediate_response' => false],
            ],
            
            'Traffic Violations' => [
                ['name' => 'Drunk Driving', 'description' => 'Driving under influence of alcohol', 'severity_level' => 3, 'requires_immediate_response' => true],
                ['name' => 'Reckless Driving', 'description' => 'Dangerous driving behavior', 'severity_level' => 2, 'requires_immediate_response' => true],
                ['name' => 'Hit and Run', 'description' => 'Leaving accident scene without reporting', 'severity_level' => 3, 'requires_immediate_response' => true],
                ['name' => 'Speeding', 'description' => 'Exceeding speed limits', 'severity_level' => 1, 'requires_immediate_response' => false],
                ['name' => 'Traffic Light Violation', 'description' => 'Running red lights', 'severity_level' => 1, 'requires_immediate_response' => false],
            ],
            
            'Public Order' => [
                ['name' => 'Public Disturbance', 'description' => 'Disrupting public peace', 'severity_level' => 1, 'requires_immediate_response' => false],
                ['name' => 'Illegal Gathering', 'description' => 'Unauthorized public assembly', 'severity_level' => 2, 'requires_immediate_response' => false],
                ['name' => 'Noise Violation', 'description' => 'Excessive noise in public', 'severity_level' => 1, 'requires_immediate_response' => false],
                ['name' => 'Public Intoxication', 'description' => 'Being drunk in public', 'severity_level' => 1, 'requires_immediate_response' => false],
            ],
        ];

        foreach ($crimeTypes as $categoryName => $types) {
            $category = CrimeCategory::where('name', $categoryName)->first();
            if ($category) {
                foreach ($types as $type) {
                    CrimeType::updateOrCreate(
                        ['name' => $type['name'], 'crime_category_id' => $category->id],
                        array_merge($type, ['crime_category_id' => $category->id])
                    );
                }
            }
        }
    }
}