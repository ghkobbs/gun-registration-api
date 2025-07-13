<?php
namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\ApiController;
use App\Http\Requests\StoreGunApplicationRequest;
use App\Http\Resources\GunApplicationResource;
use App\Models\GunApplication;
use App\Models\Document;
use App\Services\EscalationService;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class GunApplicationController extends ApiController
{
		use AuthorizesRequests;
		
    protected $escalationService;

    public function __construct(EscalationService $escalationService)
    {
        $this->escalationService = $escalationService;
    }

    public function index(Request $request)
    {
        $user = $request->user();

        $applications = GunApplication::query()
            ->when(!$user->hasRole('admin'), function ($query) use ($user) {
                $query->where('user_id', $user->id);
            })
            ->when($request->status, function ($query, $status) {
                $query->where('status', $status);
            })
            ->when($request->application_type, function ($query, $type) {
                $query->where('application_type', $type);
            })
            ->when($request->search, function ($query, $search) {
                $query->where('application_number', 'like', "%{$search}%");
            })
            ->with(['user', 'documents', 'gunRegistrations'])
            ->orderBy('created_at', 'desc')
            ->paginate($request->per_page ?? 15);

        return $this->paginatedResponse($applications, 'Applications retrieved successfully');
    }

    public function store(StoreGunApplicationRequest $request)
    {
        try {
            $application = GunApplication::create([
                'application_number' => $this->generateApplicationNumber(),
                'user_id' => $request->user()->id,
                'application_type' => $request->application_type,
                'purpose_of_ownership' => $request->purpose_of_ownership,
                'has_previous_conviction' => $request->has_previous_conviction,
                'conviction_details' => $request->conviction_details,
                'has_mental_health_issues' => $request->has_mental_health_issues,
                'mental_health_details' => $request->mental_health_details,
                'emergency_contacts' => $request->emergency_contacts,
                'status' => 'draft',
            ]);

            // Add firearm details if provided
            if ($request->has('firearm_details')) {
                $application->gunRegistrations()->create([
                    'user_id' => $request->user()->id,
                    'registration_number' => $this->generateRegistrationNumber(),
                    'firearm_type' => $request->firearm_details['firearm_type'],
                    'make' => $request->firearm_details['make'],
                    'model' => $request->firearm_details['model'],
                    'caliber' => $request->firearm_details['caliber'],
                    'serial_number' => $request->firearm_details['serial_number'],
                    'manufacture_year' => $request->firearm_details['manufacture_year'],
                    'acquisition_method' => $request->firearm_details['acquisition_method'],
                    'acquisition_date' => $request->firearm_details['acquisition_date'],
                    'purchase_price' => $request->firearm_details['purchase_price'] ?? null,
                    'dealer_name' => $request->firearm_details['dealer_name'] ?? null,
                    'registration_date' => now(),
                    'expiry_date' => now()->addYears(2),
                    'status' => 'active',
                ]);
            }

            return $this->successResponse(
                new GunApplicationResource($application->load(['user', 'documents', 'gunRegistrations'])),
                'Application created successfully',
                201
            );

        } catch (\Exception $e) {
            return $this->errorResponse('Application creation failed: ' . $e->getMessage(), 500);
        }
    }

    public function show(GunApplication $gunApplication)
    {
        $this->authorize('view', $gunApplication);

        $gunApplication->load(['user', 'documents', 'gunRegistrations', 'payments']);

        return $this->successResponse(new GunApplicationResource($gunApplication));
    }

    public function update(Request $request, GunApplication $gunApplication)
    {
        $this->authorize('update', $gunApplication);

        if ($gunApplication->status !== 'draft') {
            return $this->errorResponse('Can only update draft applications', 400);
        }

        $request->validate([
            'purpose_of_ownership' => 'sometimes|string|max:1000',
            'has_previous_conviction' => 'sometimes|boolean',
            'conviction_details' => 'sometimes|nullable|string|max:1000',
            'has_mental_health_issues' => 'sometimes|boolean',
            'mental_health_details' => 'sometimes|nullable|string|max:1000',
            'emergency_contacts' => 'sometimes|array',
        ]);

        try {
            $gunApplication->update($request->only([
                'purpose_of_ownership',
                'has_previous_conviction',
                'conviction_details',
                'has_mental_health_issues',
                'mental_health_details',
                'emergency_contacts',
            ]));

            return $this->successResponse(
                new GunApplicationResource($gunApplication),
                'Application updated successfully'
            );

        } catch (\Exception $e) {
            return $this->errorResponse('Application update failed: ' . $e->getMessage(), 500);
        }
    }

    public function submit(GunApplication $gunApplication)
    {
        $this->authorize('update', $gunApplication);

        if ($gunApplication->status !== 'draft') {
            return $this->errorResponse('Application has already been submitted', 400);
        }

        // Check if all required documents are uploaded
        $requiredDocuments = ['national_id', 'proof_of_address', 'medical_certificate'];
        $uploadedDocuments = $gunApplication->documents()->pluck('document_type')->toArray();

        $missingDocuments = array_diff($requiredDocuments, $uploadedDocuments);

        if (!empty($missingDocuments)) {
            return $this->errorResponse('Missing required documents: ' . implode(', ', $missingDocuments), 400);
        }

        try {
            $gunApplication->update([
                'status' => 'submitted',
                'submitted_at' => now(),
            ]);

            // Start escalation monitoring
            $this->escalationService->startMonitoring($gunApplication);

            return $this->successResponse(
                new GunApplicationResource($gunApplication),
                'Application submitted successfully'
            );

        } catch (\Exception $e) {
            return $this->errorResponse('Application submission failed: ' . $e->getMessage(), 500);
        }
    }

    public function uploadDocument(Request $request, GunApplication $gunApplication)
    {
        $this->authorize('update', $gunApplication);

        $request->validate([
            'document_type' => 'required|string|in:national_id,proof_of_address,medical_certificate,firearm_license,other',
            'title' => 'required|string|max:255',
            'description' => 'nullable|string|max:1000',
            'file' => 'required|file|mimes:pdf,jpg,jpeg,png|max:5120', // 5MB max
        ]);

        try {
            $file = $request->file('file');
            $path = $file->store('documents/gun-applications', 'documents');

            $document = Document::create([
                'user_id' => $request->user()->id,
                'documentable_type' => GunApplication::class,
                'documentable_id' => $gunApplication->id,
                'document_type' => $request->document_type,
                'title' => $request->title,
                'description' => $request->description,
                'file_path' => $path,
                'file_name' => $file->getClientOriginalName(),
                'file_type' => $file->getMimeType(),
                'file_size' => $file->getSize(),
                'is_required' => in_array($request->document_type, ['national_id', 'proof_of_address', 'medical_certificate']),
            ]);

            return $this->successResponse([
                'document_id' => $document->id,
                'document_type' => $document->document_type,
                'title' => $document->title,
                'uploaded_at' => $document->created_at,
            ], 'Document uploaded successfully');

        } catch (\Exception $e) {
            return $this->errorResponse('Document upload failed: ' . $e->getMessage(), 500);
        }
    }

    public function approve(Request $request, GunApplication $gunApplication)
    {
        $this->authorize('approve', $gunApplication);

        $request->validate([
            'notes' => 'nullable|string|max:1000',
        ]);

        try {
            $gunApplication->update([
                'status' => 'approved',
                'approved_by' => $request->user()->id,
                'approved_at' => now(),
            ]);

            // Update gun registration status
            $gunApplication->gunRegistrations()->update([
                'status' => 'active',
                'registration_date' => now(),
            ]);

            return $this->successResponse(
                new GunApplicationResource($gunApplication),
                'Application approved successfully'
            );

        } catch (\Exception $e) {
            return $this->errorResponse('Application approval failed: ' . $e->getMessage(), 500);
        }
    }

    public function reject(Request $request, GunApplication $gunApplication)
    {
        $this->authorize('approve', $gunApplication);

        $request->validate([
            'rejection_reason' => 'required|string|max:1000',
        ]);

        try {
            $gunApplication->update([
                'status' => 'rejected',
                'rejection_reason' => $request->rejection_reason,
                'reviewed_by' => $request->user()->id,
                'reviewed_at' => now(),
            ]);

            return $this->successResponse(
                new GunApplicationResource($gunApplication),
                'Application rejected'
            );

        } catch (\Exception $e) {
            return $this->errorResponse('Application rejection failed: ' . $e->getMessage(), 500);
        }
    }

    private function generateApplicationNumber()
    {
        return 'GA-' . date('Y') . '-' . str_pad(GunApplication::count() + 1, 6, '0', STR_PAD_LEFT);
    }

    private function generateRegistrationNumber()
    {
        return 'GR-' . date('Y') . '-' . Str::upper(Str::random(8));
    }
}