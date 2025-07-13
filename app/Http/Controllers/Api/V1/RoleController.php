<?php
namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\ApiController;
use Spatie\Permission\Models\Permission;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use App\Http\Resources\RoleResource;
use App\Http\Resources\RoleCollection;
use App\Models\Role;

class RoleController extends ApiController
{
    /**
     * Display a listing of roles.
     */
    public function index(Request $request): JsonResponse
    {
        $query = Role::with(['permissions', 'users']);

        // Search functionality
        if ($request->filled('search')) {
            $query->where('name', 'like', '%' . $request->search . '%')
                  ->orWhere('description', 'like', '%' . $request->search . '%');
        }

        // Filter by guard
        if ($request->filled('guard_name')) {
            $query->where('guard_name', $request->guard_name);
        }

        // Sort
        $sortBy = $request->get('sort_by', 'name');
        $sortDirection = $request->get('sort_direction', 'asc');
        $query->orderBy($sortBy, $sortDirection);

        $perPage = $request->get('per_page', 15);
        $roles = $query->paginate($perPage);

        return $this->paginatedResponse($roles, 'Roles retrieved successfully');
    }

    /**
     * Store a newly created role.
     */
    public function store(Request $request): JsonResponse
    {
        $validatedData = $request->validate([
            'name' => 'required|string|max:255|unique:roles,name',
            'description' => 'nullable|string|max:1000',
            'guard_name' => 'required|string|max:255',
            'permissions' => 'array',
            'permissions.*' => 'exists:permissions,id',
        ]);

        $role = Role::create([
            'name' => $validatedData['name'],
            'description' => $validatedData['description'] ?? null,
            'guard_name' => $validatedData['guard_name'],
        ]);
				
				// If permissions are provided, sync them
				if (!empty($validatedData['permissions'])) {
						$permissions = Permission::whereIn('id', $validatedData['permissions'])->get();
						$role->syncPermissions($permissions);
				}

        $role->load('permissions');

        return $this->successResponse(
						new RoleResource($role),
						'Role created successfully'
				);
    }

    /**
     * Display the specified role.
     */
    public function show(Role $role): JsonResponse
    {
        $role->load(['permissions', 'users']);

        return $this->successResponse(
						new RoleResource($role),
						'Role retrieved successfully'
				);
    }

    /**
     * Update the specified role.
     */
    public function update(Request $request, Role $role): JsonResponse
    {
        $validatedData = $request->validate([
            'name' => 'required|string|max:255|unique:roles,name,' . $role->id,
            'description' => 'nullable|string|max:1000',
            'permissions' => 'array',
            'permissions.*' => 'exists:permissions,id',
        ]);

        $role->update([
            'name' => $validatedData['name'],
            'description' => $validatedData['description'] ?? null,
        ]);

        // Sync permissions if provided
        if (isset($validatedData['permissions'])) {
            $permissions = Permission::whereIn('id', $validatedData['permissions'])->get();
            $role->syncPermissions($permissions);
        }

        $role->load('permissions');

        return $this->successResponse(
						new RoleResource($role),
						'Role updated successfully'
				);
    }

    /**
     * Remove the specified role.
     */
    public function destroy(Role $role): JsonResponse
    {
        // Check if role has users assigned
        if ($role->users()->exists()) {
            return response()->json([
                'success' => false,
                'message' => 'Cannot delete role with assigned users.',
            ], 422);
        }

        $role->delete();

        return $this->successResponse(
						null,
						'Role deleted successfully'
				);
    }

    /**
     * Get all permissions.
     */
    public function permissions(): JsonResponse
    {
        $permissions = Permission::all()->groupBy('category');

        return $this->successResponse(
						$permissions,
						'Permissions retrieved successfully'
				);
    }

    /**
     * Assign permissions to a role.
     */
    public function assignPermissions(Role $role, Request $request): JsonResponse
    {
        $request->validate([
            'permissions' => 'required|array',
            'permissions.*' => 'exists:permissions,id',
        ]);

        $permissions = Permission::whereIn('id', $request->permissions)->get();
        $role->syncPermissions($permissions);

        return $this->successResponse(
						new RoleResource($role->load('permissions')),
						'Permissions assigned successfully'
				);
    }

    /**
     * Remove permissions from a role.
     */
    public function removePermissions(Role $role, Request $request): JsonResponse
    {
        $request->validate([
            'permissions' => 'required|array',
            'permissions.*' => 'exists:permissions,id',
        ]);

        $permissions = Permission::whereIn('id', $request->permissions)->get();
        $role->revokePermissionTo($permissions);

        return $this->successResponse(
						new RoleResource($role->load('permissions')),
						'Permissions removed successfully'
				);
    }

    /**
     * Get users assigned to a role.
     */
    public function users(Role $role): JsonResponse
    {
        $users = $role->users()->get();

        return $this->successResponse(
						$users,
						'Users assigned to role retrieved successfully'
				);
    }
}