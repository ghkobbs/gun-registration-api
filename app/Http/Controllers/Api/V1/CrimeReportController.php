<?php
namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\ApiController;
use App\Http\Requests\StoreCrimeReportRequest;
use App\Http\Resources\CrimeReportResource;
use App\Models\CrimeReport;
use App\Models\CrimeEvidence;
use App\Services\LocationService;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class CrimeReportController extends ApiController
{
		use AuthorizesRequests;

    protected $locationService;

    public function __construct(LocationService $locationService)
    {
        $this->locationService = $locationService;
    }

    public function index(Request $request)
    {
        $user = $request->user();

        $reports = CrimeReport::query()
            ->when(!$user->hasRole('admin'), function ($query) use ($user) {
                $query->where('user_id', $user->id);
            })
            ->when($request->status, function ($query, $status) {
                $query->where('status', $status);
            })
            ->when($request->crime_type, function ($query, $type) {
                $query->where('crime_type_id', $type);
            })
            ->when($request->region, function ($query, $region) {
                $query->where('region_id', $region);
            })
            ->when($request->district, function ($query, $district) {
                $query->where('district_id', $district);
            })
            ->when($request->urgency, function ($query, $urgency) {
                $query->where('urgency_level', $urgency);
            })
            ->when($request->date_from, function ($query, $date) {
                $query->whereDate('incident_date', '>=', $date);
            })
            ->when($request->date_to, function ($query, $date) {
                $query->whereDate('incident_date', '<=', $date);
            })
            ->with(['crimeType', 'region', 'district', 'community', 'evidence'])
            ->orderBy('created_at', 'desc')
            ->paginate($request->per_page ?? 15);

        return $this->paginatedResponse($reports, 'Crime reports retrieved successfully');
    }

    public function store(StoreCrimeReportRequest $request)
    {
        try {
            // Generate report numbers
            $reportNumber = $this->generateReportNumber();
            $referenceCode = $this->generateReferenceCode();

            // Determine location details
            $locationDetails = $this->locationService->getLocationDetails(
                $request->latitude,
                $request->longitude,
                $request->region_id,
                $request->district_id,
                $request->community_id
            );

            $report = CrimeReport::create([
                'report_number' => $reportNumber,
                'reference_code' => $referenceCode,
                'user_id' => $request->user()->id ?? null,
                'crime_type_id' => $request->crime_type_id,
                'reporter_name' => $request->is_anonymous ? null : $request->reporter_name,
                'reporter_phone' => $request->is_anonymous ? null : $request->reporter_phone,
                'reporter_email' => $request->is_anonymous ? null : $request->reporter_email,
                'is_anonymous' => $request->is_anonymous ?? false,
                'reporting_method' => $request->reporting_method ?? 'app',
                'incident_description' => $request->incident_description,
                'incident_date' => $request->incident_date,
                'incident_location' => $request->incident_location,
                'latitude' => $request->latitude,
                'longitude' => $request->longitude,
                'region_id' => $locationDetails['region_id'],
                'district_id' => $locationDetails['district_id'],
                'community_id' => $locationDetails['community_id'],
                'suspects_count' => $request->suspects_count ?? 0,
                'victims_count' => $request->victims_count ?? 0,
                'witnesses_count' => $request->witnesses_count ?? 0,
                'urgency_level' => $request->urgency_level ?? 'medium',
                'additional_notes' => $request->additional_notes,
                'preferred_language' => $request->preferred_language ?? 'en',
                'status' => 'submitted',
            ]);

            // Auto-assign to nearest police station
            $this->autoAssignReport($report);

            return $this->successResponse([
                'report_id' => $report->id,
                'report_number' => $report->report_number,
                'reference_code' => $report->reference_code,
                'status' => $report->status,
                'created_at' => $report->created_at,
            ], 'Crime report submitted successfully', 201);

        } catch (\Exception $e) {
            return $this->errorResponse('Report submission failed: ' . $e->getMessage(), 500);
        }
    }

    public function show(CrimeReport $crimeReport)
    {
        $this->authorize('view', $crimeReport);

        $crimeReport->load([
            'crimeType.crimeCategory',
            'region',
            'district',
            'community',
            'evidence',
            'updates.updatedBy',
            'assignedTo'
        ]);

        return $this->successResponse(new CrimeReportResource($crimeReport));
    }

    public function update(Request $request, CrimeReport $crimeReport)
    {
        $this->authorize('update', $crimeReport);

        $request->validate([
            'status' => 'sometimes|in:submitted,under_investigation,assigned,in_progress,resolved,closed,cancelled',
            'assigned_to' => 'sometimes|exists:users,id',
            'notes' => 'sometimes|string|max:1000',
        ]);

        try {
            $oldStatus = $crimeReport->status;

            $crimeReport->update($request->only(['status', 'assigned_to']));

            if ($request->assigned_to && $request->assigned_to !== $crimeReport->assigned_to) {
                $crimeReport->update([
                    'assigned_to' => $request->assigned_to,
                    'assigned_at' => now(),
                ]);
            }

            // Log the update
            if ($request->notes || $request->status !== $oldStatus) {
                $crimeReport->updates()->create([
                    'updated_by' => $request->user()->id,
                    'update_type' => 'status_change',
                    'message' => $request->notes ?? "Status changed from {$oldStatus} to {$crimeReport->status}",
                    'visibility' => 'public',
                ]);
            }

            return $this->successResponse(
                new CrimeReportResource($crimeReport),
                'Report updated successfully'
            );

        } catch (\Exception $e) {
            return $this->errorResponse('Report update failed: ' . $e->getMessage(), 500);
        }
    }

    public function uploadEvidence(Request $request, CrimeReport $crimeReport)
    {
        $this->authorize('update', $crimeReport);

        $request->validate([
            'evidence_type' => 'required|in:photo,video,audio,document',
            'title' => 'required|string|max:255',
            'description' => 'nullable|string|max:1000',
            'file' => 'required|file|max:10240', // 10MB max
            'captured_at' => 'nullable|date',
        ]);

        try {
            $file = $request->file('file');
            $path = $file->store('evidence/crime-reports', 'evidence');

            $evidence = CrimeEvidence::create([
                'crime_report_id' => $crimeReport->id,
                'evidence_type' => $request->evidence_type,
                'title' => $request->title,
                'description' => $request->description,
                'file_path' => $path,
                'file_name' => $file->getClientOriginalName(),
                'file_type' => $file->getMimeType(),
                'file_size' => $file->getSize(),
                'captured_at' => $request->captured_at,
                'metadata' => $this->extractMetadata($file),
            ]);

            return $this->successResponse([
                'evidence_id' => $evidence->id,
                'evidence_type' => $evidence->evidence_type,
                'title' => $evidence->title,
                'uploaded_at' => $evidence->created_at,
            ], 'Evidence uploaded successfully');

        } catch (\Exception $e) {
            return $this->errorResponse('Evidence upload failed: ' . $e->getMessage(), 500);
        }
    }

    private function generateReportNumber()
    {
        return 'CR-' . date('Y') . '-' . str_pad(CrimeReport::count() + 1, 6, '0', STR_PAD_LEFT);
    }

    private function generateReferenceCode()
    {
        return strtoupper(substr(md5(uniqid()), 0, 8));
    }

    private function autoAssignReport($report)
    {
        // Logic to assign report to nearest police station
        // This would integrate with police station database
        // For now, we'll implement a simple assignment logic
    }

    private function extractMetadata($file)
    {
        $metadata = [];

        if (in_array($file->getMimeType(), ['image/jpeg', 'image/png'])) {
            // Extract EXIF data for images
            $exif = @exif_read_data($file->getRealPath());
            if ($exif) {
                $metadata['exif'] = $exif;
            }
        }

        return $metadata;
    }
}