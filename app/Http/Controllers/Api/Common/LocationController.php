<?php

namespace App\Http\Controllers\Api\Common;

use App\Http\Controllers\Controller;
use App\Models\UserLocation;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Exception;

class LocationController extends Controller
{
    /**
     * Update user location (Driver or Rider)
     */
    public function updateLocation(Request $request): JsonResponse
    {
        try {
            $user = $request->user();
            // Determine role from guard or request
            $role = $request->is('api/v1/driver/*') ? 'driver' : 'rider';

            if (!$user) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Unauthorized. Please login.',
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
                    'message' => 'Validation error',
                    'errors' => $validator->errors(),
                ], 422);
            }

            $location = UserLocation::updateOrCreate(
                ['user_id' => $user->id, 'role' => $role],
                [
                    'latitude' => $request->latitude,
                    'longitude' => $request->longitude,
                    'place_name' => $request->place_name,
                    'updated_at' => now(),
                ]
            );

            return response()->json([
                'status' => 'success',
                'message' => 'Location updated',
                'data' => $location
            ]);

        } catch (Exception $e) {
            Log::error('Location Update Error: ' . $e->getMessage());
            return response()->json([
                'status' => 'error',
                'message' => 'Internal server error.',
            ], 500);
        }
    }

    /**
     * Get current user location
     */
    public function getLocation(Request $request): JsonResponse
    {
        try {
            $user = $request->user();
            $role = $request->is('api/v1/driver/*') ? 'driver' : 'rider';

            $location = UserLocation::where('user_id', $user->id)
                ->where('role', $role)
                ->first();

            if (!$location) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Location not found.',
                ], 404);
            }

            return response()->json([
                'status' => 'success',
                'data' => $location
            ]);

        } catch (Exception $e) {
            return response()->json(['status' => 'error', 'message' => 'Failed to fetch location.'], 500);
        }
    }

    /**
     * Search for nearby drivers (for Riders)
     */
    public function getNearbyDrivers(Request $request): JsonResponse
    {
        try {
            $validator = Validator::make($request->all(), [
                'latitude' => 'required|numeric',
                'longitude' => 'required|numeric',
                'radius' => 'nullable|numeric|max:50', // km
            ]);

            if ($validator->fails()) {
                return response()->json(['status' => 'error', 'errors' => $validator->errors()], 422);
            }

            $latitude = $request->latitude;
            $longitude = $request->longitude;
            $radius = $request->radius ?? 10;

            // Haversine formula to find nearby drivers
            $drivers = UserLocation::selectRaw("
                    user_id, latitude, longitude,
                    ( 6371 * acos( cos( radians(?) ) * cos( radians( latitude ) ) * cos( radians( longitude ) - radians(?) ) + sin( radians(?) ) * sin( radians( latitude ) ) ) ) AS distance", 
                    [$latitude, $longitude, $latitude])
                ->where('role', 'driver')
                ->having('distance', '<', $radius)
                ->orderBy('distance')
                ->get();

            return response()->json([
                'status' => 'success',
                'data' => [
                    'drivers' => $drivers,
                    'count' => $drivers->count()
                ]
            ]);

        } catch (Exception $e) {
            Log::error('Nearby Drivers Search Error: ' . $e->getMessage());
            return response()->json(['status' => 'error', 'message' => 'Error searching for drivers.'], 500);
        }
    }
}
