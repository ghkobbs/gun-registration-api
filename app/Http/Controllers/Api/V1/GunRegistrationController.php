<?php
namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\ApiController;
use App\Models\GunRegistration;
use App\Models\GunApplication;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use App\Http\Resources\GunRegistrationResource;
use App\Http\Resources\GunRegistrationCollection;

class GunRegistrationController extends ApiController
{
    /**
     * Display a listing of gun registrations.
     */
    public function index(Request $request): JsonResponse
    {
        $query = GunRegistration::with(['user', 'gunApplication']);

        // Search functionality
        if ($request->filled('search')) {
            $query->where('registration_number', 'like', '%' . $request->search . '%')
                  ->orWhere('serial_number', 'like', '%' . $request->search . '%')
                  ->orWhereHas('user', function($q) use ($request) {
                      $q->where('first_name', 'like', '%' . $request->search . '%')
                        ->orWhere('last_name', 'like', '%' . $request->search . '%')
                        ->orWhere('email', 'like', '%' . $request->search . '%');
                  });
        }

        // Filter by status
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        // Filter by gun type
        if ($request->filled('gun_type')) {
            $query->where('gun_type', $request->gun_type);
        }

        // Filter by license validity
        if ($request->filled('license_status')) {
            if ($request->license_status === 'valid') {
                $query->where('license_expires_at', '>', now());
            } elseif ($request->license_status === 'expired') {
                $query->where('license_expires_at', '<=', now());
            } elseif ($request->license_status === 'expiring_soon') {
                $query->whereBetween('license_expires_at', [now(), now()->addDays(30)]);
            }
        }

        // Sort
        $sortBy = $request->get('sort_by', 'created_at');
        $sortDirection = $request->get('sort_direction', 'desc');
        $query->orderBy($sortBy, $sortDirection);

        $perPage = $request->get('per_page', 15);
        $registrations = $query->paginate($perPage);

        return $this->paginatedResponse($registrations, 'Gun registrations retrieved successfully');
    }

    /**
     * Store a newly created gun registration.
     */
    public function store(Request $request): JsonResponse
    {
        $validatedData = $request->validate([
            'gun_application_id' => 'required|exists:gun_applications,id',
            'registration_number' => 'required|string|max:255|unique:gun_registrations,registration_number',
            'serial_number' => 'required|string|max:255|unique:gun_registrations,serial_number',
            'gun_type' => 'required|string|max:255',
            'gun_model' => 'required|string|max:255',
            'manufacturer' => 'required|string|max:255',
            'caliber' => 'required|string|max:100',
            'barrel_length' => 'nullable|numeric|min:0',
            'gun_condition' => 'required|string|max:255',
            'license_expires_at' => 'required|date|after:today',
            'status' => 'required|string|in:active,suspended,revoked',
            'notes' => 'nullable|string|max:1000',
        ]);

        $gunApplication = GunApplication::findOrFail($validatedData['gun_application_id']);
        $validatedData['user_id'] = $gunApplication->user_id;
        $validatedData['issued_at'] = now();

        $registration = GunRegistration::create($validatedData);
        $registration->load(['user', 'gunApplication']);

        return $this->successResponse(
						new GunRegistrationResource($registration),
						'Gun registration created successfully'
				);
    }

    /**
     * Display the specified gun registration.
     */
    public function show(GunRegistration $gunRegistration): JsonResponse
    {
        $gunRegistration->load(['user', 'gunApplication']);

        return $this->successResponse(
						new GunRegistrationResource($gunRegistration),
						'Gun registration retrieved successfully'
				);
    }

    /**
     * Update the specified gun registration.
     */
    public function update(Request $request, GunRegistration $gunRegistration): JsonResponse
    {
        $validatedData = $request->validate([
            'gun_type' => 'required|string|max:255',
            'gun_model' => 'required|string|max:255',
            'manufacturer' => 'required|string|max:255',
            'caliber' => 'required|string|max:100',
            'barrel_length' => 'nullable|numeric|min:0',
            'gun_condition' => 'required|string|max:255',
            'license_expires_at' => 'required|date|after:today',
            'status' => 'required|string|in:active,suspended,revoked',
            'notes' => 'nullable|string|max:1000',
        ]);

        $gunRegistration->update($validatedData);
        $gunRegistration->load(['user', 'gunApplication']);

        return $this->successResponse(
						new GunRegistrationResource($gunRegistration),
						'Gun registration updated successfully'
				);
    }

    /**
     * Remove the specified gun registration.
     */
    public function destroy(GunRegistration $gunRegistration): JsonResponse
    {
        $gunRegistration->delete();

        return $this->successResponse(
						null,
						'Gun registration deleted successfully'
				);
    }

    /**
     * Get current user's gun registrations.
     */
    public function myRegistrations(Request $request): JsonResponse
    {
        $user = $request->user();
        $registrations = $user->gunRegistrations()->with('gunApplication')->get();

        return $this->successResponse(
						new GunRegistrationCollection($registrations),
						'Gun registrations retrieved successfully'
				);
    }

    /**
     * Suspend a gun registration.
     */
    public function suspend(GunRegistration $gunRegistration, Request $request): JsonResponse
    {
        $request->validate([
            'reason' => 'required|string|max:1000',
        ]);

        $gunRegistration->update([
            'status' => 'suspended',
            'notes' => $request->reason,
        ]);

        return $this->successResponse(
						new GunRegistrationResource($gunRegistration),
						'Gun registration suspended successfully'
				);
    }

    /**
     * Revoke a gun registration.
     */
    public function revoke(GunRegistration $gunRegistration, Request $request): JsonResponse
    {
        $request->validate([
            'reason' => 'required|string|max:1000',
        ]);

        $gunRegistration->update([
            'status' => 'revoked',
            'notes' => $request->reason,
        ]);

        return $this->successResponse(
						new GunRegistrationResource($gunRegistration),
						'Gun registration revoked successfully'
				);
    }

    /**
     * Reactivate a gun registration.
     */
    public function reactivate(GunRegistration $gunRegistration, Request $request): JsonResponse
    {
        $request->validate([
            'reason' => 'required|string|max:1000',
        ]);

        $gunRegistration->update([
            'status' => 'active',
            'notes' => $request->reason,
        ]);

        return $this->successResponse(
						new GunRegistrationResource($gunRegistration),
						'Gun registration reactivated successfully'
				);
    }

    /**
     * Get registration statistics.
     */
    public function statistics(): JsonResponse
    {
        $stats = [
            'total_registrations' => GunRegistration::count(),
            'active_registrations' => GunRegistration::where('status', 'active')->count(),
            'suspended_registrations' => GunRegistration::where('status', 'suspended')->count(),
            'revoked_registrations' => GunRegistration::where('status', 'revoked')->count(),
            'expired_licenses' => GunRegistration::where('license_expires_at', '<=', now())->count(),
            'expiring_soon' => GunRegistration::whereBetween('license_expires_at', [now(), now()->addDays(30)])->count(),
        ];

        return $this->successResponse(
						$stats,
						'Gun registration statistics retrieved successfully'
				);
    }
}