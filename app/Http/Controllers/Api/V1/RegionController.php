<?php
namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\ApiController;
use App\Models\Region;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use App\Http\Resources\RegionResource;
use App\Http\Resources\RegionCollection;

class RegionController extends ApiController
{
    /**
     * Display a listing of regions.
     */
    public function index(Request $request): JsonResponse
    {
        $query = Region::withCount(['districts', 'crimeReports']);

        // Search functionality
        if ($request->filled('search')) {
            $query->where('name', 'like', '%' . $request->search . '%')
                  ->orWhere('code', 'like', '%' . $request->search . '%');
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
        $regions = $query->paginate($perPage);

        return $this->paginatedResponse($regions, 'Regions retrieved successfully');
    }

    /**
     * Store a newly created region.
     */
    public function store(Request $request): JsonResponse
    {
        $validatedData = $request->validate([
            'name' => 'required|string|max:255|unique:regions,name',
            'code' => 'required|string|max:10|unique:regions,code',
            'is_active' => 'boolean',
        ]);

        $region = Region::create($validatedData);

        return $this->successResponse(new RegionResource($region), 'Region created successfully');
    }

    /**
     * Display the specified region.
     */
    public function show(Region $region): JsonResponse
    {
        $region->load(['districts' => function($query) {
            $query->where('is_active', true)->withCount('communities');
        }]);

        // Get statistics
        $stats = [
            'total_districts' => $region->districts->count(),
            'total_communities' => $region->districts->sum('communities_count'),
            'recent_crime_reports' => $region->crimeReports()
                                            ->where('created_at', '>=', now()->subDays(30))
                                            ->count(),
            'total_crime_reports' => $region->crimeReports()->count(),
        ];

        return $this->successResponse(new RegionResource($region, $stats), 'Region retrieved successfully');
    }

    /**
     * Update the specified region.
     */
    public function update(Request $request, Region $region): JsonResponse
    {
        $validatedData = $request->validate([
            'name' => 'required|string|max:255|unique:regions,name,' . $region->id,
            'code' => 'required|string|max:10|unique:regions,code,' . $region->id,
            'is_active' => 'boolean',
        ]);

        $region->update($validatedData);

        return $this->successResponse(new RegionResource($region), 'Region updated successfully');
    }

    /**
     * Remove the specified region.
     */
    public function destroy(Region $region): JsonResponse
    {
        // Check if region has associated districts
        if ($region->districts()->exists()) {
            return $this->errorResponse('Cannot delete region with associated districts', 422);
        }

        $region->delete();

        return $this->successResponse(null, 'Region deleted successfully');
    }

    /**
     * Get districts for a specific region.
     */
    public function districts(Region $region): JsonResponse
    {
        $districts = $region->getActiveDistricts();

        return $this->successResponse(new RegionCollection($districts), 'Active districts retrieved successfully');
    }

    /**
     * Get crime statistics for a region.
     */
    public function statistics(Region $region, Request $request): JsonResponse
    {
        $days = $request->get('days', 30);
        $startDate = now()->subDays($days);
        $endDate = now();

        $stats = $region->getCrimeStatsForPeriod($startDate, $endDate);

        return $this->successResponse($stats, 'Crime statistics retrieved successfully');
    }

    /**
     * Toggle the active status of a region.
     */
    public function toggleStatus(Region $region): JsonResponse
    {
        $region->update(['is_active' => !$region->is_active]);

        $status = $region->is_active ? 'activated' : 'deactivated';

        return $this->successResponse(
						new RegionResource($region),
						"Region {$status} successfully"
				);
    }
}