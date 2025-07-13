<?php
namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\ApiController;
use App\Http\Requests\RegisterUserRequest;
use App\Http\Requests\LoginUserRequest;
use App\Http\Resources\UserResource;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;
use Illuminate\Validation\ValidationException;
use Illuminate\Support\Str;

class AuthController extends ApiController
{
    public function register(RegisterUserRequest $request)
    {
        try {
            $user = User::create([
                'first_name' => $request->first_name,
                'last_name' => $request->last_name,
                'middle_name' => $request->middle_name,
                'email' => $request->email,
                'phone_number' => $request->phone_number,
                'national_id' => $request->national_id,
                'password' => Hash::make($request->password),
                'user_type' => $request->user_type ?? 'client',
            ]);

            // Create user profile
            $user->profile()->create([
                'date_of_birth' => $request->date_of_birth,
                'gender' => $request->gender,
                'occupation' => $request->occupation,
            ]);

            // Create user address if provided
            if ($request->has('address')) {
                $user->addresses()->create([
                    'street_address' => $request->address['street_address'],
                    'city' => $request->address['city'],
                    'region' => $request->address['region'],
                    'postal_code' => $request->address['postal_code'],
                    'is_primary' => true,
                ]);
            }

            // Validate National ID (integrate with NIA)
            $this->validateNationalId($user);

            $token = $user->createToken('auth_token')->plainTextToken;

            return $this->successResponse([
                'user' => new UserResource($user),
                'token' => $token,
                'token_type' => 'Bearer',
            ], 'User registered successfully', 201);

        } catch (\Exception $e) {
            return $this->errorResponse('Registration failed: ' . $e->getMessage(), 500);
        }
    }

    public function login(LoginUserRequest $request)
    {
        $user = User::where('email', $request->email)
                   ->orWhere('phone_number', $request->email)
                   ->first();

        if (!$user || !Hash::check($request->password, $user->password)) {
            throw ValidationException::withMessages([
                'email' => ['The provided credentials are incorrect.'],
            ]);
        }

        if ($user->status !== 'active') {
            return $this->errorResponse('Account is not active. Please contact support.', 403);
        }

        // Revoke existing tokens
        $user->tokens()->delete();

        $token = $user->createToken('auth_token')->plainTextToken;

        return $this->successResponse([
            'user' => new UserResource($user),
            'token' => $token,
            'token_type' => 'Bearer',
        ], 'Login successful');
    }

    public function logout(Request $request)
    {
        $request->user()->currentAccessToken()->delete();

        return $this->successResponse(null, 'Logged out successfully');
    }

    public function forgotPassword(Request $request)
    {
        $request->validate(['email' => 'required|email']);

        $status = Password::sendResetLink(
            $request->only('email')
        );

        if ($status === Password::RESET_LINK_SENT) {
            return $this->successResponse(null, 'Password reset link sent to your email');
        }

        return $this->errorResponse('Unable to send reset link', 400);
    }

    public function resetPassword(Request $request)
    {
        $request->validate([
            'token' => 'required',
            'email' => 'required|email',
            'password' => 'required|min:8|confirmed',
        ]);

        $status = Password::reset(
            $request->only('email', 'password', 'password_confirmation', 'token'),
            function ($user, $password) {
                $user->forceFill([
                    'password' => Hash::make($password)
                ])->setRememberToken(Str::random(60));

                $user->save();
            }
        );

        if ($status === Password::PASSWORD_RESET) {
            return $this->successResponse(null, 'Password reset successful');
        }

        return $this->errorResponse('Password reset failed', 400);
    }

    private function validateNationalId($user)
    {
        // Integrate with National ID Authority API
        // This is a placeholder - implement actual API call
        try {
            // Mock validation - replace with actual API call
            $isValid = $this->callNationalIdApi($user->national_id);
            
            if ($isValid) {
                $user->update([
                    'national_id_status' => 'verified',
                    'national_id_verified_at' => now(),
                ]);
            } else {
                $user->update(['national_id_status' => 'failed']);
            }
        } catch (\Exception $e) {
            // Handle API failures
            $user->update(['national_id_status' => 'pending']);
        }
    }

    private function callNationalIdApi($nationalId)
    {
        // Implement actual API call to National ID Authority
        // Return true for now
        return true;
    }
}