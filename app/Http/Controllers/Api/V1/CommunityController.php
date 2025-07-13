<?php
namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\ApiController;
use App\Models\Community;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use App\Http\Resources\CommunityResource;
use App\Http\Resources\CommunityCollection;

class CommunityController extends ApiController
{
    /**
     * Display a listing of communities.
     */
    public function index(Request $request): JsonResponse
    {
        $query = Community::with(['district.region'])->withCount('crimeReports');

        // Search functionality
        if ($request->filled('search')) {
            $query->where('name', 'like', '%' . $request->search . '%')
                  ->orWhere('code', 'like', '%' . $request->search . '%')
                  ->orWhereHas('district', function($q) use ($request) {
                      $q->where('name', 'like', '%' . $request->search . '%');
                  });
        }

        // Filter by region
        if ($request->filled('region_id')) {
            $query->whereHas('district', function($q) use ($request) {
                $q->where('region_id', $request->region_id);
            });
        }

        // Filter by district
        if ($request->filled('district_id')) {
            $query->where('district_id', $request->district_id);
        }

        // Filter by status
        if ($request->filled('status')) {
            $query->where('is_active', $request->status === 'active');
        }

        // Sort
        $sortBy = $request->get('sort_by', 'name');
        $sortDirection = $request->get('sort_direction', 'asc');
        $query->orderBy($sortBy, $sortDirection);

        $perPage = $request->get('per_page', 15);
        $communities = $query->paginate($perPage);

        return $this->paginatedResponse($communities, 'Communities retrieved successfully');
    }

    /**
     * Store a newly created community.
     */
    public function store(Request $request): JsonResponse
    {
        $validatedData = $request->validate([
            'district_id' => 'required|exists:districts,id',
            'name' => 'required|string|max:255|unique:communities,name',
            'code' => 'required|string|max:10|unique:communities,code',
            'latitude' => 'nullable|numeric|between:-90,90',
            'longitude' => 'nullable|numeric|between:-180,180',
            'boundary' => 'nullable|string',
            'is_active' => 'boolean',
        ]);

        $community = Community::create($validatedData);
        $community->load('district.region');

        return $this->successResponse(new CommunityResource($community), 'Community created successfully');
    }

    /**
     * Display the specified community.
     */
    public function show(Community $community): JsonResponse
    {
        $community->load('district.region');

        // Get statistics
        $stats = [
            'recent_crime_reports' => $community->crimeReports()
                                               ->where('created_at', '>=', now()->subDays(30))
                                               ->count(),
            'total_crime_reports' => $community->crimeReports()->count(),
            'has_coordinates' => $community->has_coordinates,
        ];

        // Get recent crime reports
        $recentReports = $community->getRecentCrimeReports(10);

        // Get crime hotspots if community has coordinates
        $hotspots = $community->has_coordinates ? $community->getCrimeHotspots() : collect();

        return $this->successResponse(
						new CommunityResource($community, $stats, $recentReports, $hotspots),
						'Community retrieved successfully'
				);
    }

    /**
     * Update the specified community.
     */
    public function update(Request $request, Community $community): JsonResponse
    {
        $validatedData = $request->validate([
            'district_id' => 'required|exists:districts,id',
            'name' => 'required|string|max:255|unique:communities,name,' . $community->id,
            'code' => 'required|string|max:10|unique:communities,code,' . $community->id,
            'latitude' => 'nullable|numeric|between:-90,90',
            'longitude' => 'nullable|numeric|between:-180,180',
            'boundary' => 'nullable|string',
            'is_active' => 'boolean',
        ]);

        $community->update($validatedData);
        $community->load('district.region');

        return $this->successResponse(new CommunityResource($community), 'Community updated successfully');
    }

    /**
     * Remove the specified community.
     */
    public function destroy(Community $community): JsonResponse
    {
        // Check if community has associated crime reports
        if ($community->crimeReports()->exists()) {
            return $this->errorResponse('Cannot delete community with associated crime reports', 422);
        }

        $community->delete();

        return $this->successResponse(null, 'Community deleted successfully');
    }

    /**
     * Get crime hotspots for a community.
     */
    public function hotspots(Community $community, Request $request): JsonResponse
    {
        $radius = $request->get('radius', 5);
        $hotspots = $community->getCrimeHotspots($radius);

        return $this->successResponse(
						$hotspots,
						'Crime hotspots retrieved successfully'
				);
    }

    /**
     * Find communities within radius of coordinates.
     */
    public function findByLocation(Request $request): JsonResponse
    {
        $request->validate([
            'latitude' => 'required|numeric|between:-90,90',
            'longitude' => 'required|numeric|between:-180,180',
            'radius' => 'nullable|numeric|min:1|max:50',
        ]);

        $latitude = $request->latitude;
        $longitude = $request->longitude;
        $radius = $request->get('radius', 10);

        $communities = Community::selectRaw('
            *, ( 6371 * acos( cos( radians(?) ) *
            cos( radians( latitude ) ) *
            cos( radians( longitude ) - radians(?) ) +
            sin( radians(?) ) *
            sin( radians( latitude ) ) ) ) AS distance
        ', [$latitude, $longitude, $latitude])
        ->having('distance', '<=', $radius)
        ->whereNotNull('latitude')
        ->whereNotNull('longitude')
        ->where('is_active', true)
        ->orderBy('distance')
        ->get();

        return $this->successResponse(
						new CommunityCollection($communities),
						'Communities found within radius'
				);
    }

    /**
     * Toggle the active status of a community.
     */
    public function toggleStatus(Community $community): JsonResponse
    {
        $community->update(['is_active' => !$community->is_active]);

        $status = $community->is_active ? 'activated' : 'deactivated';

        return $this->successResponse(
						new CommunityResource($community),
						"Community {$status} successfully"
				);
    }
}