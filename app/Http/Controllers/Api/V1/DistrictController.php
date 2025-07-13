<?php
namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\ApiController;
use App\Models\District;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use App\Http\Resources\DistrictResource;
use App\Http\Resources\DistrictCollection;

class DistrictController extends ApiController
{
    /**
     * Display a listing of districts.
     */
    public function index(Request $request): JsonResponse
    {
        $query = District::with('region')->withCount(['communities', 'crimeReports']);

        // Search functionality
        if ($request->filled('search')) {
            $query->where('name', 'like', '%' . $request->search . '%')
                  ->orWhere('code', 'like', '%' . $request->search . '%')
                  ->orWhereHas('region', function($q) use ($request) {
                      $q->where('name', 'like', '%' . $request->search . '%');
                  });
        }

        // Filter by region
        if ($request->filled('region_id')) {
            $query->where('region_id', $request->region_id);
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
        $districts = $query->paginate($perPage);

        return $this->paginatedResponse($districts, 'Districts retrieved successfully');
    }

    /**
     * Store a newly created district.
     */
    public function store(Request $request): JsonResponse
    {
        $validatedData = $request->validate([
            'region_id' => 'required|exists:regions,id',
            'name' => 'required|string|max:255|unique:districts,name',
            'code' => 'required|string|max:10|unique:districts,code',
            'is_active' => 'boolean',
        ]);

        $district = District::create($validatedData);
        $district->load('region');

        return $this->successResponse(new DistrictResource($district), 'District created successfully');
    }

    /**
     * Display the specified district.
     */
    public function show(District $district): JsonResponse
    {
        $district->load(['region', 'communities' => function($query) {
            $query->where('is_active', true);
        }]);

        // Get statistics
        $stats = [
            'total_communities' => $district->communities->count(),
            'recent_crime_reports' => $district->crimeReports()
                                              ->where('created_at', '>=', now()->subDays(30))
                                              ->count(),
            'total_crime_reports' => $district->crimeReports()->count(),
        ];

        return $this->successResponse(new DistrictResource($district, $stats), 'District retrieved successfully');
    }

    /**
     * Update the specified district.
     */
    public function update(Request $request, District $district): JsonResponse
    {
        $validatedData = $request->validate([
            'region_id' => 'required|exists:regions,id',
            'name' => 'required|string|max:255|unique:districts,name,' . $district->id,
            'code' => 'required|string|max:10|unique:districts,code,' . $district->id,
            'is_active' => 'boolean',
        ]);

        $district->update($validatedData);
        $district->load('region');

        return $this->successResponse(new DistrictResource($district), 'District updated successfully');
    }

    /**
     * Remove the specified district.
     */
    public function destroy(District $district): JsonResponse
    {
        // Check if district has associated communities
        if ($district->communities()->exists()) {
            return $this->errorResponse('Cannot delete district with associated communities', 422);
        }

        $district->delete();

        return $this->successResponse(null, 'District deleted successfully');
    }

    /**
     * Get communities for a specific district.
     */
    public function communities(District $district): JsonResponse
    {
        $communities = $district->getActiveCommunities();

        return $this->successResponse(new DistrictCollection($communities), 'Active communities retrieved successfully');
    }

    /**
     * Get crime statistics for a district.
     */
    public function statistics(District $district, Request $request): JsonResponse
    {
        $days = $request->get('days', 30);
        $startDate = now()->subDays($days);
        $endDate = now();

        $stats = $district->getCrimeStatsForPeriod($startDate, $endDate);

        return $this->successResponse($stats, 'Crime statistics retrieved successfully');
    }

    /**
     * Toggle the active status of a district.
     */
    public function toggleStatus(District $district): JsonResponse
    {
        $district->update(['is_active' => !$district->is_active]);

        $status = $district->is_active ? 'activated' : 'deactivated';

        return $this->successResponse(
						new DistrictResource($district),
						"District {$status} successfully"
				);
    }
}