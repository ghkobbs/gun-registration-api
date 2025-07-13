<?php
namespace App\Services;

use App\Models\USSDSession;
use App\Models\CrimeReport;
use App\Models\CrimeType;
use App\Models\Region;
use App\Models\District;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class USSDService
{
    protected $sessionTimeout = 300; // 5 minutes
    protected $maxSessionData = 1000; // Max characters in session data

    /**
     * Get or create USSD session
     */
    public function getOrCreateSession($sessionId, $phoneNumber)
    {
        return USSDSession::firstOrCreate(
            ['session_id' => $sessionId],
            [
                'phone_number' => $phoneNumber,
                'current_menu' => 'main',
                'session_data' => [],
                'language' => 'en',
                'status' => 'active',
                'last_activity' => now(),
            ]
        );
    }

    /**
     * Update session activity
     */
    public function updateSessionActivity(USSDSession $session, $currentMenu = null, $sessionData = null)
    {
        $updateData = ['last_activity' => now()];

        if ($currentMenu) {
            $updateData['current_menu'] = $currentMenu;
        }

        if ($sessionData !== null) {
            $updateData['session_data'] = $sessionData;
        }

        $session->update($updateData);
    }

    /**
     * Check if session is expired
     */
    public function isSessionExpired(USSDSession $session)
    {
        return $session->last_activity->addSeconds($this->sessionTimeout)->isPast();
    }

    /**
     * End session
     */
    public function endSession(USSDSession $session, $status = 'completed')
    {
        $session->update([
            'status' => $status,
            'last_activity' => now(),
        ]);
    }

    /**
     * Clean expired sessions
     */
    public function cleanExpiredSessions()
    {
        $expiredSessions = USSDSession::where('status', 'active')
            ->where('last_activity', '<', now()->subSeconds($this->sessionTimeout))
            ->get();

        foreach ($expiredSessions as $session) {
            $this->endSession($session, 'timeout');
        }

        return $expiredSessions->count();
    }

    /**
     * Get menu text in user's language
     */
    public function getMenuText($menuKey, $language = 'en', $params = [])
    {
        $texts = $this->getMenuTexts($language);
        $text = $texts[$menuKey] ?? $texts['default'][$menuKey] ?? $menuKey;

        // Replace parameters
        foreach ($params as $key => $value) {
            $text = str_replace('{' . $key . '}', $value, $text);
        }

        return $text;
    }

    /**
     * Get all menu texts for a language
     */
    protected function getMenuTexts($language)
    {
        $cacheKey = "ussd_menu_texts_{$language}";

        return Cache::remember($cacheKey, 3600, function () use ($language) {
            return [
                'en' => [
                    'welcome' => "Welcome to Crime Reporting System",
                    'main_menu' => "1. Report a Crime\n2. Track Report\n3. Emergency Call\n4. Change Language\n0. Exit",
                    'select_crime_type' => "Select crime type:",
                    'describe_incident' => "Describe the incident (max 160 characters):",
                    'select_region' => "Select your region:",
                    'select_district' => "Select your district:",
                    'select_urgency' => "Select urgency level:\n1. Low\n2. Medium\n3. High\n4. Critical/Emergency",
                    'confirm_report' => "Confirm your report:",
                    'submit_or_cancel' => "1. Submit Report\n2. Cancel",
                    'report_submitted' => "Report submitted successfully!\nReference Code: {reference_code}",
                    'track_report' => "Enter your 8-digit reference code:",
                    'invalid_selection' => "Invalid option. Please try again.",
                    'thank_you' => "Thank you for using Crime Reporting System.",
                    'emergency_message' => "Calling emergency services...\nDial 999 for immediate assistance.",
                ],
                'tw' => [
                    'welcome' => "Akwaaba wo Crime Reporting System mu",
                    'main_menu' => "1. Bɔ amane wɔ bone bi ho\n2. Hwehwɛ wo amanneɛ\n3. Emergency frɛ\n4. Sesa kasa\n0. Fi",
                    // Add more Twi translations...
                ],
                'default' => [
                    'welcome' => "Welcome to Crime Reporting System",
                    'main_menu' => "1. Report a Crime\n2. Track Report\n3. Emergency Call\n4. Change Language\n0. Exit",
                    // Default English fallbacks...
                ]
            ];
        });
    }

    /**
     * Format menu with options
     */
    public function formatMenu($title, $options, $language = 'en')
    {
        $menu = "CON " . $this->getMenuText($title, $language) . "\n";

        foreach ($options as $key => $option) {
            $menu .= $key . ". " . $option . "\n";
        }

        return rtrim($menu);
    }

    /**
     * Get crime types for menu
     */
    public function getCrimeTypesForMenu($limit = 8)
    {
        return Cache::remember('ussd_crime_types', 1800, function () use ($limit) {
            return CrimeType::where('is_active', true)
                           ->orderBy('name')
                           ->limit($limit)
                           ->pluck('name', 'id')
                           ->toArray();
        });
    }

    /**
     * Get regions for menu
     */
    public function getRegionsForMenu($limit = 10)
    {
        return Cache::remember('ussd_regions', 1800, function () use ($limit) {
            return Region::where('is_active', true)
                        ->orderBy('name')
                        ->limit($limit)
                        ->pluck('name', 'id')
                        ->toArray();
        });
    }

    /**
     * Get districts for menu
     */
    public function getDistrictsForMenu($regionId, $limit = 10)
    {
        return Cache::remember("ussd_districts_{$regionId}", 1800, function () use ($regionId, $limit) {
            return District::where('region_id', $regionId)
                          ->where('is_active', true)
                          ->orderBy('name')
                          ->limit($limit)
                          ->pluck('name', 'id')
                          ->toArray();
        });
    }

    /**
     * Validate user input
     */
    public function validateInput($input, $type, $options = [])
    {
        switch ($type) {
            case 'menu_option':
                return is_numeric($input) && $input >= 0 && $input <= count($options);
            
            case 'text':
                return !empty(trim($input)) && strlen($input) <= 160;
            
            case 'reference_code':
                return preg_match('/^[A-Z0-9]{8}$/', strtoupper($input));
            
            case 'phone_number':
                return preg_match('/^[0-9]{10}$/', $input);
            
            default:
                return true;
        }
    }

    /**
     * Generate report reference code
     */
    public function generateReferenceCode()
    {
        do {
            $code = strtoupper(substr(md5(uniqid()), 0, 8));
        } while (CrimeReport::where('reference_code', $code)->exists());

        return $code;
    }

    /**
     * Get report status message
     */
    public function getReportStatusMessage($status, $language = 'en')
    {
        $messages = [
            'en' => [
                'submitted' => 'Report received and being processed',
                'under_investigation' => 'Under investigation',
                'assigned' => 'Assigned to officer',
                'in_progress' => 'Investigation in progress',
                'resolved' => 'Case resolved',
                'closed' => 'Case closed',
            ],
            'tw' => [
                'submitted' => 'Wɔagye wo amanneɛ na wɔreyɛ ho adwuma',
                'under_investigation' => 'Wɔreyɛ nhwehwɛmu',
                'assigned' => 'Wɔde ama adwumayɛfoɔ',
                'in_progress' => 'Nhwehwɛmu rekɔ so',
                'resolved' => 'Wɔadi asɛm no ho dwuma',
                'closed' => 'Wɔato asɛm no mu',
            ],
        ];

        return $messages[$language][$status] ?? $messages['en'][$status] ?? 'Unknown status';
    }

    /**
     * Log USSD interaction
     */
    public function logInteraction($sessionId, $phoneNumber, $menu, $input, $response)
    {
        Log::info('USSD Interaction', [
            'session_id' => $sessionId,
            'phone_number' => $phoneNumber,
            'menu' => $menu,
            'input' => $input,
            'response_length' => strlen($response),
        ]);
    }

    /**
     * Get session statistics
     */
    public function getSessionStatistics($dateFrom = null, $dateTo = null)
    {
        $query = USSDSession::query();

        if ($dateFrom) {
            $query->whereDate('created_at', '>=', $dateFrom);
        }

        if ($dateTo) {
            $query->whereDate('created_at', '<=', $dateTo);
        }

        return [
            'total_sessions' => $query->count(),
            'completed_sessions' => $query->where('status', 'completed')->count(),
            'timeout_sessions' => $query->where('status', 'timeout')->count(),
            'cancelled_sessions' => $query->where('status', 'cancelled')->count(),
            'active_sessions' => $query->where('status', 'active')->count(),
            'average_session_duration' => $this->calculateAverageSessionDuration($query),
        ];
    }

    /**
     * Calculate average session duration
     */
    protected function calculateAverageSessionDuration($query)
    {
        $sessions = $query->whereIn('status', ['completed', 'timeout', 'cancelled'])
                         ->get();

        if ($sessions->isEmpty()) {
            return 0;
        }

        $totalSeconds = $sessions->sum(function ($session) {
            return $session->created_at->diffInSeconds($session->last_activity);
        });

        return round($totalSeconds / $sessions->count(), 2);
    }
}