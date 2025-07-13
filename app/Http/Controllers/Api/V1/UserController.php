<?php
namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\ApiController;
use App\Http\Resources\UserResource;
use App\Models\User;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;

class UserController extends ApiController
{
    use AuthorizesRequests;

    public function index(Request $request)
    {
        $this->authorize('viewAny', User::class);

        $users = User::with(['profile', 'addresses', 'roles'])
                    ->when($request->search, function ($query, $search) {
                        $query->where('first_name', 'like', "%{$search}%")
                              ->orWhere('last_name', 'like', "%{$search}%")
                              ->orWhere('email', 'like', "%{$search}%");
                    })
                    ->when($request->user_type, function ($query, $type) {
                        $query->where('user_type', $type);
                    })
                    ->when($request->status, function ($query, $status) {
                        $query->where('status', $status);
                    })
                    ->paginate($request->per_page ?? 15);

        return $this->paginatedResponse($users, 'Users retrieved successfully');
    }

    public function show(User $user)
    {
        $this->authorize('view', $user);

        $user->load(['profile', 'addresses', 'roles']);

        return $this->successResponse(new UserResource($user));
    }

    public function updateProfile(Request $request)
    {
        $user = $request->user();

        $request->validate([
            'first_name' => 'sometimes|string|max:255',
            'last_name' => 'sometimes|string|max:255',
            'middle_name' => 'sometimes|nullable|string|max:255',
            'phone_number' => [
                'sometimes',
                'string',
                'max:20',
                Rule::unique('users')->ignore($user->id),
            ],
            'date_of_birth' => 'sometimes|date|before:today',
            'gender' => 'sometimes|in:male,female,other',
            'occupation' => 'sometimes|string|max:255',
            'employer' => 'sometimes|string|max:255',
            'bio' => 'sometimes|string|max:1000',
            'address' => 'sometimes|array',
            'address.street_address' => 'required_with:address|string|max:255',
            'address.city' => 'required_with:address|string|max:255',
            'address.region' => 'required_with:address|string|max:255',
            'address.postal_code' => 'sometimes|string|max:20',
        ]);

        try {
            // Update user basic info
            $user->update($request->only([
                'first_name', 'last_name', 'middle_name', 'phone_number'
            ]));

            // Update profile
            $user->profile()->updateOrCreate(
                ['user_id' => $user->id],
                $request->only([
                    'date_of_birth', 'gender', 'occupation', 'employer', 'bio'
                ])
            );

            // Update primary address
            if ($request->has('address')) {
                $user->addresses()->updateOrCreate(
                    ['user_id' => $user->id, 'is_primary' => true],
                    array_merge($request->address, ['is_primary' => true])
                );
            }

            $user->load(['profile', 'addresses']);

            return $this->successResponse(
                new UserResource($user),
                'Profile updated successfully'
            );

        } catch (\Exception $e) {
            return $this->errorResponse('Profile update failed: ' . $e->getMessage(), 500);
        }
    }

    public function uploadAvatar(Request $request)
    {
        $request->validate([
            'avatar' => 'required|image|mimes:jpeg,png,jpg,gif|max:2048',
        ]);

        try {
            $user = $request->user();

            // Delete old avatar if exists
            if ($user->profile && $user->profile->profile_photo_path) {
                Storage::delete($user->profile->profile_photo_path);
            }

            // Store new avatar
            $path = $request->file('avatar')->store('avatars', 'public');

            // Update profile
            $user->profile()->updateOrCreate(
                ['user_id' => $user->id],
                ['profile_photo_path' => $path]
            );

            return $this->successResponse([
                'avatar_url' => Storage::url($path),
            ], 'Avatar uploaded successfully');

        } catch (\Exception $e) {
            return $this->errorResponse('Avatar upload failed: ' . $e->getMessage(), 500);
        }
    }

    public function update(Request $request, User $user)
    {
        $this->authorize('update', $user);

        $request->validate([
            'status' => 'sometimes|in:active,suspended,pending_verification',
            'user_type' => 'sometimes|in:client,admin,staff,law_enforcement',
            'roles' => 'sometimes|array',
            'roles.*' => 'exists:roles,id',
        ]);

        try {
            $user->update($request->only(['status', 'user_type']));

            // Update roles if provided
            if ($request->has('roles')) {
                $user->roles()->sync($request->roles);
            }

            $user->load(['profile', 'addresses', 'roles']);

            return $this->successResponse(
                new UserResource($user),
                'User updated successfully'
            );

        } catch (\Exception $e) {
            return $this->errorResponse('User update failed: ' . $e->getMessage(), 500);
        }
    }

    public function destroy(User $user)
    {
        $this->authorize('delete', $user);

        try {
            $user->delete();

            return $this->successResponse(null, 'User deleted successfully');

        } catch (\Exception $e) {
            return $this->errorResponse('User deletion failed: ' . $e->getMessage(), 500);
        }
    }
}