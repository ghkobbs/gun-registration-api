<?php

use App\Http\Controllers\Api\V1\AuthController;
use App\Http\Controllers\Api\V1\CommunityController;
use App\Http\Controllers\Api\V1\CrimeCategoryController;
use App\Http\Controllers\Api\V1\CrimeReportController;
use App\Http\Controllers\Api\V1\DistrictController;
use App\Http\Controllers\Api\V1\DocumentController;
use App\Http\Controllers\Api\V1\GunApplicationController;
use App\Http\Controllers\Api\V1\GunRegistrationController;
use App\Http\Controllers\Api\V1\NotificationController;
use App\Http\Controllers\Api\V1\NotificationTemplateController;
use App\Http\Controllers\Api\V1\PaymentController;
use App\Http\Controllers\Api\V1\RegionController;
use App\Http\Controllers\Api\V1\RoleController;
use App\Http\Controllers\Api\V1\UserController;
use App\Http\Controllers\Api\V1\USSDController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
*/

// Public routes
Route::prefix('v1')->group(function () {
		// Health check
		Route::get('/health', function () {
				return response()->json([
        'status' => 'OK',
        'timestamp' => now(),
        'version' => '1.0.0',
    	]);
		});
    // Authentication routes
    Route::post('/register', [AuthController::class, 'register']);
    Route::post('/login', [AuthController::class, 'login']);
    Route::post('/forgot-password', [AuthController::class, 'forgotPassword']);
    Route::post('/reset-password', [AuthController::class, 'resetPassword']);
    
    // Crime reporting public routes
    Route::post('/crime-reports', [CrimeReportController::class, 'store']);
    Route::get('/crime-categories', [CrimeCategoryController::class, 'index']);
    Route::get('/regions', [RegionController::class, 'index']);
    Route::get('/districts', [DistrictController::class, 'index']);
    Route::get('/communities', [CommunityController::class, 'index']);
    
    // USSD routes
    Route::post('/ussd', [USSDController::class, 'handle']);

	Route::middleware('auth:api')->group(function () {
    // User routes
    Route::get('/user', function (Request $request) {
        return $request->user();
    });
    Route::put('/user/profile', [UserController::class, 'updateProfile']);
    Route::post('/user/upload-avatar', [UserController::class, 'uploadAvatar']);
    
    // Gun registration routes
    Route::apiResource('gun-applications', GunApplicationController::class);
    Route::post('/gun-applications/{id}/documents', [GunApplicationController::class, 'uploadDocument']);
    Route::post('/gun-applications/{id}/submit', [GunApplicationController::class, 'submit']);
    
    // Crime reporting authenticated routes
    Route::apiResource('crime-reports', CrimeReportController::class)->except(['index']);
    Route::post('/crime-reports/{id}/evidence', [CrimeReportController::class, 'uploadEvidence']);
    
    // Admin routes
    Route::middleware('role:admin')->group(function () {
        Route::apiResource('users', UserController::class);
        Route::apiResource('roles', RoleController::class);
        // Route::get('/admin/dashboard', [AdminController::class, 'dashboard']);
        // Route::get('/admin/reports', [AdminController::class, 'reports']);
    });
    
    // Logout
    Route::post('/logout', [AuthController::class, 'logout']);

		// Crime Categories
    Route::apiResource('crime-categories', CrimeCategoryController::class)->except('index');
    Route::post('crime-categories/{crimeCategory}/toggle-status', [CrimeCategoryController::class, 'toggleStatus']);

    // Regions
    Route::apiResource('regions', RegionController::class)->except('index');
    Route::get('regions/{region}/districts', [RegionController::class, 'districts']);
    Route::get('regions/{region}/statistics', [RegionController::class, 'statistics']);
    Route::post('regions/{region}/toggle-status', [RegionController::class, 'toggleStatus']);

    // Districts
    Route::apiResource('districts', DistrictController::class)->except('index');
    Route::get('districts/{district}/communities', [DistrictController::class, 'communities']);
    Route::get('districts/{district}/statistics', [DistrictController::class, 'statistics']);
    Route::post('districts/{district}/toggle-status', [DistrictController::class, 'toggleStatus']);

    // Communities
    Route::apiResource('communities', CommunityController::class)->except(['index']);
    Route::get('communities/{community}/hotspots', [CommunityController::class, 'hotspots']);
    Route::get('communities/find-by-location', [CommunityController::class, 'findByLocation']);
    Route::post('communities/{community}/toggle-status', [CommunityController::class, 'toggleStatus']);

    // Gun Registrations
    Route::apiResource('gun-registrations', GunRegistrationController::class);
    Route::get('my-gun-registrations', [GunRegistrationController::class, 'myRegistrations']);
    Route::post('gun-registrations/{gunRegistration}/suspend', [GunRegistrationController::class, 'suspend']);
    Route::post('gun-registrations/{gunRegistration}/revoke', [GunRegistrationController::class, 'revoke']);
    Route::post('gun-registrations/{gunRegistration}/reactivate', [GunRegistrationController::class, 'reactivate']);
    Route::get('gun-registrations-statistics', [GunRegistrationController::class, 'statistics']);

    // Roles
    Route::apiResource('roles', RoleController::class);
    Route::get('permissions', [RoleController::class, 'permissions']);
    Route::post('roles/{role}/assign-permissions', [RoleController::class, 'assignPermissions']);
    Route::post('roles/{role}/remove-permissions', [RoleController::class, 'removePermissions']);
    Route::get('roles/{role}/users', [RoleController::class, 'users']);

			// Document routes
			Route::apiResource('documents', DocumentController::class);
			Route::post('documents/{document}/verify', [DocumentController::class, 'verify']);
			Route::post('documents/{document}/reject', [DocumentController::class, 'reject']);
			Route::get('documents/{document}/download', [DocumentController::class, 'download'])->name('documents.download');

			// Payment routes
			Route::apiResource('payments', PaymentController::class)->except(['update', 'destroy']);
			Route::post('payments/verify', [PaymentController::class, 'verify'])->name('payments.verify');
			Route::post('payments/{payment}/refund', [PaymentController::class, 'refund']);

    // Notifications
    Route::get('notifications', [NotificationController::class, 'index']);
    Route::get('notifications/{notification}', [NotificationController::class, 'show']);
    Route::post('notifications/send', [NotificationController::class, 'send']);
    Route::post('notifications/send-bulk', [NotificationController::class, 'sendBulk']);
    Route::post('notifications/mark-as-read', [NotificationController::class, 'markAsRead']);
    Route::post('notifications/mark-all-as-read', [NotificationController::class, 'markAllAsRead']);
    Route::delete('notifications/{notification}', [NotificationController::class, 'destroy']);

    // Notification Templates
    Route::apiResource('notification-templates', NotificationTemplateController::class);
    Route::post('notification-templates/{template}/preview', [NotificationTemplateController::class, 'preview']);
	});
});