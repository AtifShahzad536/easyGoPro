<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Auth\ApiAuthController;
use App\Http\Controllers\Auth\Api\DriverDocumentController;
use Illuminate\Support\Facades\Broadcast;

// Register broadcasting routes with Sanctum middleware
Broadcast::routes(['middleware' => ['auth:sanctum']]);

// Driver API Controllers
use App\Http\Controllers\Api\Driver\DriverStatisticController;
use App\Http\Controllers\Api\Driver\DriverStatusController;
use App\Http\Controllers\Api\Driver\DriverProfileController;
use App\Http\Controllers\Api\Driver\DriverRideController;

// Rider API Controllers
use App\Http\Controllers\Api\Rider\RiderProfileController;
use App\Http\Controllers\Api\Rider\RiderLocationController;
use App\Http\Controllers\Api\Rider\RideBookingController;

// Common API Controllers
use App\Http\Controllers\Api\Common\LocationController;
use App\Http\Controllers\Api\Common\CarpoolController;

/*
|--------------------------------------------------------------------------
| API Routes — Easygo
|--------------------------------------------------------------------------
| Auth is role-based via Laravel Sanctum
|--------------------------------------------------------------------------
| Structure:
|   /v1/auth/*     → Public (no token)
|   /v1/driver/*   → Driver protected
|   /v1/rider/*    → Rider protected
*/

// ═══════════════════════════════════════════════════════════════════════════════
// PUBLIC AUTHENTICATION ROUTES
// ═══════════════════════════════════════════════════════════════════════════════
Route::prefix('v1/auth')->group(function () {
    // Registration & Login
    Route::post('/register', [ApiAuthController::class, 'register']);
    Route::post('/login', [ApiAuthController::class, 'login']);

    // Phone number checks
    Route::post('/check-driver-phone', [ApiAuthController::class, 'checkDriverPhone']);
    Route::post('/check-rider-phone', [ApiAuthController::class, 'checkRiderPhone']);
});


// ═══════════════════════════════════════════════════════════════════════════════
// DRIVER PROTECTED ROUTES
// ═══════════════════════════════════════════════════════════════════════════════
Route::middleware('auth:sanctum')->prefix('v1/driver')->group(function () {

    // ─────────────────────────────────────────────────────────────────
    // Profile & Account
    // ─────────────────────────────────────────────────────────────────
    Route::get('/me', function (Request $request) {
        $user = $request->user();
        $userData = $user->toArray();
        if ($user->profile_photo) {
            $userData['profile_photo_url'] = asset('storage/' . $user->profile_photo);
        }
        return response()->json(['user' => $userData, 'role' => 'driver']);
    });

    // Driver Profile APIs
    Route::get('/profile', [DriverProfileController::class, 'getProfile']);
    Route::put('/profile', [DriverProfileController::class, 'updateProfile']);

    Route::post('/vehicle/register', [ApiAuthController::class, 'registerVehicle']);
    Route::post('/documents/upload', [DriverDocumentController::class, 'bulkUpload']);

    // ─────────────────────────────────────────────────────────────────
    // Status & Location
    // ─────────────────────────────────────────────────────────────────
    Route::get('/status', [DriverStatusController::class, 'getStatus']);
    Route::put('/status', [DriverStatusController::class, 'updateStatus']);

    Route::get('/location', [LocationController::class, 'getLocation']);
    Route::post('/location/update', [LocationController::class, 'updateLocation']);

    // ─────────────────────────────────────────────────────────────────
    // Statistics
    // ─────────────────────────────────────────────────────────────────
    Route::prefix('/statistics')->group(function () {
        Route::get('/', [DriverStatisticController::class, 'getMyStatistics']);
        Route::get('/dashboard', [DriverStatisticController::class, 'getDashboardSummary']);
        Route::get('/trips', [DriverStatisticController::class, 'getTripStatistics']);
        Route::get('/earnings', [DriverStatisticController::class, 'getEarningsHistory']);
        Route::post('/update', [DriverStatisticController::class, 'updateStatistics']);
    });

    // ─────────────────────────────────────────────────────────────────
    // Carpool Rides
    // ─────────────────────────────────────────────────────────────────
    Route::prefix('/carpool')->group(function () {
        Route::post('/publish', [CarpoolController::class, 'publishRide']);
        Route::get('/my-rides', [CarpoolController::class, 'myRides']);
        Route::put('/edit/{rideId}', [CarpoolController::class, 'editRide']);
        Route::post('/cancel/{rideId}', [CarpoolController::class, 'cancelRide']);
        Route::delete('/delete/{rideId}', [CarpoolController::class, 'deleteRide']);
    });

    // ─────────────────────────────────────────────────────────────────
    // Standard Ride Management
    // ─────────────────────────────────────────────────────────────────
    Route::prefix('/rides')->group(function () {
        Route::get('/available', [DriverRideController::class, 'getAvailableRides']);
        Route::get('/current', [DriverRideController::class, 'getCurrentRide']);
        Route::get('/history', [DriverRideController::class, 'getRideHistory']);
        Route::patch('/{rideId}/accept', [DriverRideController::class, 'acceptRide']);
        Route::patch('/{rideId}/cancel', [DriverRideController::class, 'cancelRide']);
        Route::patch('/{rideId}/start', [DriverRideController::class, 'startRide']);
        Route::patch('/{rideId}/complete', [DriverRideController::class, 'completeRide']);
    });
});


// ═══════════════════════════════════════════════════════════════════════════════
// RIDER PROTECTED ROUTES
// ═══════════════════════════════════════════════════════════════════════════════
Route::middleware('auth:sanctum')->prefix('v1/rider')->group(function () {

    // ─────────────────────────────────────────────────────────────────
    // Profile & Account (WITHOUT display_name in response)
    // ─────────────────────────────────────────────────────────────────
    Route::get('/profile', [RiderProfileController::class, 'getProfile']);
    Route::put('/profile', [RiderProfileController::class, 'updateProfile']);

    // ─────────────────────────────────────────────────────────────────
    // Location & Nearby
    // ─────────────────────────────────────────────────────────────────
    Route::get('/location', [LocationController::class, 'getLocation']);
    Route::post('/location/update', [LocationController::class, 'updateLocation']);
    Route::get('/drivers/nearby', [LocationController::class, 'findNearbyDrivers']);

    // ─────────────────────────────────────────────────────────────────
    // Carpool Search
    // ─────────────────────────────────────────────────────────────────
    Route::get('/carpool/search', [CarpoolController::class, 'availableRides']);

    // ─────────────────────────────────────────────────────────────────
    // Destination & Saved Places
    // ─────────────────────────────────────────────────────────────────
    Route::get('/destination-screen', [RiderLocationController::class, 'getDestinationScreenData']);

    // Saved Places
    Route::prefix('/saved-places')->group(function () {
        Route::get('/', [RiderLocationController::class, 'getSavedPlaces']);
        Route::post('/', [RiderLocationController::class, 'savePlace']);
        Route::put('/{id}', [RiderLocationController::class, 'updatePlace']);
        Route::delete('/{id}', [RiderLocationController::class, 'deletePlace']);
    });

    // Recent Searches
    Route::prefix('/recent-searches')->group(function () {
        Route::get('/', [RiderLocationController::class, 'getRecentSearches']);
        Route::post('/', [RiderLocationController::class, 'saveRecentSearch']);
        Route::delete('/{id}', [RiderLocationController::class, 'deleteRecentSearch']);
        Route::delete('/', [RiderLocationController::class, 'clearRecentSearches']);
    });

    // Location Search
    Route::get('/search-locations', [RiderLocationController::class, 'searchLocations']);
    Route::get('/place-details/{place_id}', [RiderLocationController::class, 'getPlaceDetails']);

    // ─────────────────────────────────────────────────────────────────
    // Ride Booking (with Stops - Max 4)
    // ─────────────────────────────────────────────────────────────────
    Route::post('/estimate-fare', [RideBookingController::class, 'estimateFare']);
    Route::post('/standard-book-ride', [RideBookingController::class, 'standardBookRide']);
    Route::get('/rides', [RideBookingController::class, 'getRides']);
    Route::get('/rides/active', [RideBookingController::class, 'getActiveRide']);
    Route::get('/rides/history', [RideBookingController::class, 'getRideHistory']);
    Route::get('/rides/{rideId}', [RideBookingController::class, 'getRideDetails']);
    Route::post('/rides/{rideId}/cancel', [RideBookingController::class, 'cancelRide']);
});
