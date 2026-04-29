<?php

namespace App\Http\Controllers\Api\Common;

use App\Http\Controllers\Controller;
use App\Models\UserLocation;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Throwable;

class LocationController extends Controller
{
    /**
     * Update user location (Driver or Rider)
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function updateLocation(Request $request): JsonResponse
    {
        try {
            $user = $request->user();
            $role = $request->attributes->get('role', 'driver'); // Get role from middleware or default to driver

            if (!$user) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Unauthorized. Please login first.',
                ], 401);
            }

            $validator = Validator::make($request->all(), [
                'latitude' => 'required|numeric|between:-90,90',
                'longitude' => 'required|numeric|between:-180,180',
                'place_name' => 'nullable|string|max:255',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Validation failed',
                    'errors' => $validator->errors(),
                ], 422);
            }

            // Update or create location
            $location = UserLocation::updateOrCreate(
                [
                    'user_id' => $user->id,
                    'role' => $role,
                ],
                [
                    'latitude' => $request->latitude,
                    'longitude' => $request->longitude,
                    'place_name' => $request->place_name,
                    'updated_at' => now(),
                ]
            );

            return response()->json([
                'status' => 'success',
                'message' => 'Location updated successfully',
                'data' => [
                    'user_id' => $user->id,
                    'role' => $role,
                    'latitude' => $location->latitude,
                    'longitude' => $location->longitude,
                    'place_name' => $location->place_name,
                    'updated_at' => $location->updated_at->toDateTimeString(),
                ],
            ], 200);

        } catch (Throwable $e) {
            Log::error('Location update error: ' . $e->getMessage(), [
                'user_id' => $request->user()?->id,
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json([
                'status' => 'error',
                'message' => 'An error occurred while updating location.',
            ], 500);
        }
    }

    /**
     * Get current user location
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function getLocation(Request $request): JsonResponse
    {
        try {
            $user = $request->user();
            $role = $request->attributes->get('role', 'driver');

            if (!$user) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Unauthorized. Please login first.',
                ], 401);
            }

            $location = UserLocation::byUser($user->id, $role)->first();

            if (!$location) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Location not found. Please update location first.',
                ], 404);
            }

            return response()->json([
                'status' => 'success',
                'data' => [
                    'user_id' => $location->user_id,
                    'role' => $location->role,
                    'latitude' => $location->latitude,
                    'longitude' => $location->longitude,
                    'place_name' => $location->place_name,
                    'updated_at' => $location->updated_at->toDateTimeString(),
                ],
            ], 200);

        } catch (Throwable $e) {
            Log::error('Location get error: ' . $e->getMessage());

            return response()->json([
                'status' => 'error',
                'message' => 'An error occurred while fetching location.',
            ], 500);
        }
    }

    /**
     * Get location by user ID and role (for admin or matching)
     *
     * @param Request $request
     * @param int $userId
     * @return JsonResponse
     */
    public function getLocationByUser(Request $request, $userId): JsonResponse
    {
        try {
            $role = $request->query('role', 'driver');

            $location = UserLocation::byUser($userId, $role)->first();

            if (!$location) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Location not found for this user.',
                ], 404);
            }

            return response()->json([
                'status' => 'success',
                'data' => [
                    'user_id' => $location->user_id,
                    'role' => $location->role,
                    'latitude' => $location->latitude,
                    'longitude' => $location->longitude,
                    'place_name' => $location->place_name,
                    'updated_at' => $location->updated_at->toDateTimeString(),
                ],
            ], 200);

        } catch (Throwable $e) {
            Log::error('Location get by user error: ' . $e->getMessage());

            return response()->json([
                'status' => 'error',
                'message' => 'An error occurred.',
            ], 500);
        }
    }

    /**
     * Find nearby drivers (for rider app)
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function findNearbyDrivers(Request $request): JsonResponse
    {
        try {
            $validator = Validator::make($request->all(), [
                'latitude' => 'required|numeric|between:-90,90',
                'longitude' => 'required|numeric|between:-180,180',
                'radius' => 'nullable|numeric|min:1|max:50', // in km, default 5km
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Validation failed',
                    'errors' => $validator->errors(),
                ], 422);
            }

            $radius = $request->radius ?? 5; // Default 5km

            // Get nearby drivers using Haversine formula
            $drivers = UserLocation::byRole('driver')
                ->selectRaw('
                    user_id,
                    latitude,
                    longitude,
                    place_name,
                    updated_at,
                    ( 6371 * acos( cos( radians(?) ) *
                    cos( radians( latitude ) ) *
                    cos( radians( longitude ) - radians(?) ) +
                    sin( radians(?) ) *
                    sin( radians( latitude ) ) ) ) AS distance',
                    [$request->latitude, $request->longitude, $request->latitude]
                )
                ->having('distance', '<', $radius)
                ->orderBy('distance')
                ->get();

            return response()->json([
                'status' => 'success',
                'data' => [
                    'drivers' => $drivers,
                    'total_found' => $drivers->count(),
                    'search_radius_km' => $radius,
                ],
            ], 200);

        } catch (Throwable $e) {
            Log::error('Find nearby drivers error: ' . $e->getMessage());

            return response()->json([
                'status' => 'error',
                'message' => 'An error occurred while finding nearby drivers.',
            ], 500);
        }
    }
}
