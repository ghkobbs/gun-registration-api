<?php
namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\ApiController;
use App\Models\USSDSession;
use App\Models\CrimeReport;
use App\Models\CrimeType;
use App\Models\Region;
use App\Models\District;
use App\Services\USSDService;
use Illuminate\Http\Request;

class USSDController extends ApiController
{
    protected $ussdService;

    public function __construct(USSDService $ussdService)
    {
        $this->ussdService = $ussdService;
    }

    public function handle(Request $request)
    {
        $request->validate([
            'sessionId' => 'required|string',
            'serviceCode' => 'required|string',
            'phoneNumber' => 'required|string',
            'text' => 'nullable|string',
        ]);

        try {
            $session = $this->getOrCreateSession($request);
            $response = $this->processUSSDRequest($session, $request->text);

            return response($response, 200)->header('Content-Type', 'text/plain');

        } catch (\Exception $e) {
            return response('END An error occurred. Please try again later.', 200)
                ->header('Content-Type', 'text/plain');
        }
    }

    private function getOrCreateSession(Request $request)
    {
        return USSDSession::firstOrCreate(
            ['session_id' => $request->sessionId],
            [
                'phone_number' => $request->phoneNumber,
                'current_menu' => 'main',
                'session_data' => [],
                'language' => 'en',
                'last_activity' => now(),
            ]
        );
    }

    private function processUSSDRequest($session, $text)
    {
        $input = trim($text);
        $inputs = explode('*', $input);
        $currentInput = end($inputs);

        // Update session activity
        $session->update(['last_activity' => now()]);

        switch ($session->current_menu) {
            case 'main':
                return $this->handleMainMenu($session, $currentInput);
            
            case 'language':
                return $this->handleLanguageSelection($session, $currentInput);
            
            case 'report_crime':
                return $this->handleCrimeTypeSelection($session, $currentInput);
            
            case 'crime_description':
                return $this->handleCrimeDescription($session, $currentInput);
            
            case 'location_selection':
                return $this->handleLocationSelection($session, $currentInput);
            
            case 'urgency_selection':
                return $this->handleUrgencySelection($session, $currentInput);
            
            case 'confirm_report':
                return $this->handleReportConfirmation($session, $currentInput);
            
            case 'track_report':
                return $this->handleReportTracking($session, $currentInput);
            
            default:
                return $this->handleMainMenu($session, $currentInput);
        }
    }

    private function handleMainMenu($session, $input)
    {
        if (empty($input)) {
            $session->update(['current_menu' => 'main']);
            
            return "CON Welcome to Crime Reporting System\n" .
                   "1. Report a Crime\n" .
                   "2. Track Report\n" .
                   "3. Emergency Call\n" .
                   "4. Change Language\n" .
                   "0. Exit";
        }

        switch ($input) {
            case '1':
                return $this->startCrimeReport($session);
            
            case '2':
                return $this->startReportTracking($session);
            
            case '3':
                return "END Calling emergency services...\nDial 999 for immediate assistance.";
            
            case '4':
                return $this->showLanguageMenu($session);
            
            case '0':
                $session->update(['status' => 'completed']);
                return "END Thank you for using Crime Reporting System.";
            
            default:
                return "CON Invalid option. Please try again.\n" .
                       "1. Report a Crime\n" .
                       "2. Track Report\n" .
                       "3. Emergency Call\n" .
                       "4. Change Language\n" .
                       "0. Exit";
        }
    }

    private function startCrimeReport($session)
    {
        $session->update([
            'current_menu' => 'report_crime',
            'session_data' => []
        ]);

        $crimeTypes = CrimeType::where('is_active', true)->take(8)->get();
        $menu = "CON Select crime type:\n";
        
        foreach ($crimeTypes as $index => $type) {
            $menu .= ($index + 1) . ". " . $type->name . "\n";
        }
        
        $menu .= "0. Back to main menu";
        
        return $menu;
    }

    private function handleCrimeTypeSelection($session, $input)
    {
        if ($input == '0') {
            return $this->handleMainMenu($session, '');
        }

        $crimeTypes = CrimeType::where('is_active', true)->take(8)->get();
        $selectedType = $crimeTypes->get($input - 1);

        if (!$selectedType) {
            return "CON Invalid selection. Please try again.\n" .
                   $this->startCrimeReport($session);
        }

        $sessionData = $session->session_data;
        $sessionData['crime_type_id'] = $selectedType->id;
        $sessionData['crime_type_name'] = $selectedType->name;

        $session->update([
            'current_menu' => 'crime_description',
            'session_data' => $sessionData
        ]);

        return "CON Describe the incident (max 160 characters):\n" .
               "Type your description and press send.";
    }

    private function handleCrimeDescription($session, $input)
    {
        if (empty($input)) {
            return "CON Please provide a description of the incident:\n" .
                   "Type your description and press send.";
        }

        $sessionData = $session->session_data;
        $sessionData['incident_description'] = $input;

        $session->update([
            'current_menu' => 'location_selection',
            'session_data' => $sessionData
        ]);

        $regions = Region::where('is_active', true)->take(8)->get();
        $menu = "CON Select your region:\n";
        
        foreach ($regions as $index => $region) {
            $menu .= ($index + 1) . ". " . $region->name . "\n";
        }
        
        return $menu;
    }

    private function handleLocationSelection($session, $input)
    {
        $sessionData = $session->session_data;

        if (!isset($sessionData['region_id'])) {
            // Selecting region
            $regions = Region::where('is_active', true)->take(8)->get();
            $selectedRegion = $regions->get($input - 1);

            if (!$selectedRegion) {
                return "CON Invalid selection. Please select your region again.";
            }

            $sessionData['region_id'] = $selectedRegion->id;
            $sessionData['region_name'] = $selectedRegion->name;

            $session->update(['session_data' => $sessionData]);

            $districts = District::where('region_id', $selectedRegion->id)
                                ->where('is_active', true)
                                ->take(8)
                                ->get();

            $menu = "CON Select your district:\n";
            foreach ($districts as $index => $district) {
                $menu .= ($index + 1) . ". " . $district->name . "\n";
            }

            return $menu;
        }

        // Selecting district
        $districts = District::where('region_id', $sessionData['region_id'])
                            ->where('is_active', true)
                            ->take(8)
                            ->get();
        $selectedDistrict = $districts->get($input - 1);

        if (!$selectedDistrict) {
            return "CON Invalid selection. Please select your district again.";
        }

        $sessionData['district_id'] = $selectedDistrict->id;
        $sessionData['district_name'] = $selectedDistrict->name;

        $session->update([
            'current_menu' => 'urgency_selection',
            'session_data' => $sessionData
        ]);

        return "CON Select urgency level:\n" .
               "1. Low\n" .
               "2. Medium\n" .
               "3. High\n" .
               "4. Critical/Emergency";
    }

    private function handleUrgencySelection($session, $input)
    {
        $urgencyLevels = ['low', 'medium', 'high', 'critical'];
        $selectedUrgency = $urgencyLevels[$input - 1] ?? null;

        if (!$selectedUrgency) {
            return "CON Invalid selection. Please select urgency level:\n" .
                   "1. Low\n" .
                   "2. Medium\n" .
                   "3. High\n" .
                   "4. Critical/Emergency";
        }

        $sessionData = $session->session_data;
        $sessionData['urgency_level'] = $selectedUrgency;

        $session->update([
            'current_menu' => 'confirm_report',
            'session_data' => $sessionData
        ]);

        return "CON Confirm your report:\n" .
               "Crime: " . $sessionData['crime_type_name'] . "\n" .
               "Location: " . $sessionData['district_name'] . "\n" .
               "Urgency: " . ucfirst($selectedUrgency) . "\n" .
               "1. Submit Report\n" .
               "2. Cancel";
    }

    private function handleReportConfirmation($session, $input)
    {
        if ($input == '2') {
            $session->update(['status' => 'cancelled']);
            return "END Report cancelled.";
        }

        if ($input == '1') {
            return $this->submitCrimeReport($session);
        }

        return "CON Invalid selection.\n" .
               "1. Submit Report\n" .
               "2. Cancel";
    }

    private function submitCrimeReport($session)
    {
        try {
            $sessionData = $session->session_data;
            
            $report = CrimeReport::create([
                'report_number' => $this->generateReportNumber(),
                'reference_code' => $this->generateReferenceCode(),
                'crime_type_id' => $sessionData['crime_type_id'],
                'reporter_phone' => $session->phone_number,
                'incident_description' => $sessionData['incident_description'],
                'incident_date' => now(),
                'incident_location' => $sessionData['district_name'],
                'region_id' => $sessionData['region_id'],
                'district_id' => $sessionData['district_id'],
                'urgency_level' => $sessionData['urgency_level'],
                'reporting_method' => 'ussd',
                'status' => 'submitted',
                'preferred_language' => $session->language,
            ]);

            $session->update(['status' => 'completed']);

            return "END Report submitted successfully!\n" .
                   "Reference Code: " . $report->reference_code . "\n" .
                   "Track your report by dialing this code again.";

        } catch (\Exception $e) {
            return "END Error submitting report. Please try again later.";
        }
    }

    private function startReportTracking($session)
    {
        $session->update(['current_menu' => 'track_report']);

        return "CON Enter your 8-digit reference code:";
    }

    private function handleReportTracking($session, $input)
    {
        $report = CrimeReport::where('reference_code', strtoupper($input))
                            ->where('reporter_phone', $session->phone_number)
                            ->first();

        if (!$report) {
            return "END Report not found. Please check your reference code.";
        }

        $statusMessage = $this->getStatusMessage($report->status);

        return "END Report Status: " . $statusMessage . "\n" .
               "Submitted: " . $report->created_at->format('d/m/Y H:i') . "\n" .
               "Reference: " . $report->reference_code;
    }

    private function showLanguageMenu($session)
    {
        $session->update(['current_menu' => 'language']);

        return "CON Select Language:\n" .
               "1. English\n" .
               "2. Twi\n" .
               "3. Ga\n" .
               "4. Ewe";
    }

    private function handleLanguageSelection($session, $input)
    {
        $languages = ['en', 'tw', 'ga', 'ee'];
        $selectedLanguage = $languages[$input - 1] ?? null;

        if (!$selectedLanguage) {
            return $this->showLanguageMenu($session);
        }

        $session->update(['language' => $selectedLanguage]);

        return "END Language updated successfully.";
    }

    private function generateReportNumber()
    {
        return 'CR-' . date('Y') . '-' . str_pad(CrimeReport::count() + 1, 6, '0', STR_PAD_LEFT);
    }

    private function generateReferenceCode()
    {
        return strtoupper(substr(md5(uniqid()), 0, 8));
    }

    private function getStatusMessage($status)
    {
        $messages = [
            'submitted' => 'Report received and being processed',
            'under_investigation' => 'Under investigation',
            'assigned' => 'Assigned to officer',
            'in_progress' => 'Investigation in progress',
            'resolved' => 'Case resolved',
            'closed' => 'Case closed',
        ];

        return $messages[$status] ?? 'Unknown status';
    }
}