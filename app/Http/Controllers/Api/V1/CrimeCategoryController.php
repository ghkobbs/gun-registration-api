<?php
namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\ApiController;
use App\Models\CrimeCategory;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use App\Http\Resources\CrimeCategoryResource;
use App\Http\Resources\CrimeCategoryCollection;

class CrimeCategoryController extends ApiController
{
    /**
     * Display a listing of crime categories.
     */
    public function index(Request $request): JsonResponse
    {
        $query = CrimeCategory::with('crimeTypes');

        // Search functionality
        if ($request->filled('search')) {
            $query->where('name', 'like', '%' . $request->search . '%')
                  ->orWhere('description', 'like', '%' . $request->search . '%');
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
        $categories = $query->paginate($perPage);

        return $this->paginatedResponse($categories, 'Crime categories retrieved successfully');
    }

    /**
     * Store a newly created crime category.
     */
    public function store(Request $request): JsonResponse
    {
        $validatedData = $request->validate([
            'name' => 'required|string|max:255|unique:crime_categories,name',
            'description' => 'nullable|string|max:1000',
            'color' => 'nullable|string|max:7|regex:/^#[a-fA-F0-9]{6}$/',
            'icon' => 'nullable|string|max:100',
            'is_active' => 'boolean',
        ]);

        $category = CrimeCategory::create($validatedData);

        return $this->successResponse(new CrimeCategoryResource($category), 'Crime category created successfully');
    }

    /**
     * Display the specified crime category.
     */
    public function show(CrimeCategory $crimeCategory): JsonResponse
    {
        $crimeCategory->load('crimeTypes');

        // Get statistics
        $stats = [
            'total_crime_types' => $crimeCategory->crimeTypes->count(),
            'active_crime_types' => $crimeCategory->crimeTypes->where('is_active', true)->count(),
            'recent_reports' => $crimeCategory->crimeReports()
                                            ->where('created_at', '>=', now()->subDays(30))
                                            ->count(),
        ];

        return $this->successResponse(new CrimeCategoryResource($crimeCategory, $stats), 'Crime category retrieved successfully');
    }

    /**
     * Update the specified crime category.
     */
    public function update(Request $request, CrimeCategory $crimeCategory): JsonResponse
    {
        $validatedData = $request->validate([
            'name' => 'required|string|max:255|unique:crime_categories,name,' . $crimeCategory->id,
            'description' => 'nullable|string|max:1000',
            'color' => 'nullable|string|max:7|regex:/^#[a-fA-F0-9]{6}$/',
            'icon' => 'nullable|string|max:100',
            'is_active' => 'boolean',
        ]);

        $crimeCategory->update($validatedData);

        return $this->successResponse(new CrimeCategoryResource($crimeCategory->load('crimeTypes')), 'Crime category updated successfully');
    }

    /**
     * Remove the specified crime category.
     */
    public function destroy(CrimeCategory $crimeCategory): JsonResponse
    {
        // Check if category has associated crime types
        if ($crimeCategory->crimeTypes()->exists()) {
            return response()->json([
                'success' => false,
                'message' => 'Cannot delete category with associated crime types.',
            ], 422);
        }

        $crimeCategory->delete();

        return $this->successResponse(null, 'Crime category deleted successfully');
    }

    /**
     * Toggle the active status of a crime category.
     */
    public function toggleStatus(CrimeCategory $crimeCategory): JsonResponse
    {
        $crimeCategory->update(['is_active' => !$crimeCategory->is_active]);

        $status = $crimeCategory->is_active ? 'activated' : 'deactivated';

        return $this->successResponse(
						new CrimeCategoryResource($crimeCategory),
						"Crime category {$status} successfully"
				);
    }
}