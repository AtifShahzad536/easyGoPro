<?php

namespace App\Http\Controllers\Api\Driver;

use App\Http\Controllers\Controller;
use App\Models\Ride;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class DriverRideController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth:sanctum');
    }

    /**
     * GET /api/v1/driver/rides/available
     * Get available rides (status: searching) near driver's location
     */
    public function getAvailableRides(Request $request): JsonResponse
    {
        $driver = auth()->user();

        // Get rides that are searching for drivers
        $rides = Ride::with(['rider'])
            ->where('status', 'searching')
            ->where('ride_type', $driver->vehicle->type ?? 'economy')
            ->whereNull('driver_id')
            ->whereDoesntHave('driver') // Ensure no driver assigned
            ->latest()
            ->limit(10)
            ->get();

        return response()->json([
            'status' => 'success',
            'data' => [
                'rides' => $rides,
                'count' => $rides->count(),
            ],
        ]);
    }

    /**
     * POST /api/v1/driver/rides/{rideId}/accept
     * Accept a ride (driver accepts the ride request)
     */
    public function acceptRide(Request $request, $rideId): JsonResponse
    {
        $driver = auth()->user();

        // Check if driver is available
        if (!$driver->is_available) {
            return response()->json([
                'status' => 'error',
                'message' => 'Driver is not available. Please go online first.',
            ], 403);
        }

        // Check if driver is already on a trip
        if ($driver->status === 'on_trip') {
            return response()->json([
                'status' => 'error',
                'message' => 'Driver is already on a trip. Complete current ride first.',
            ], 403);
        }

        try {
            return DB::transaction(function () use ($request, $rideId, $driver) {
                $ride = Ride::where('id', $rideId)
                    ->where('status', 'searching')
                    ->whereNull('driver_id')
                    ->lockForUpdate()
                    ->first();

                if (!$ride) {
                    return response()->json([
                        'status' => 'error',
                        'message' => 'Ride not available for acceptance',
                    ], 404);
                }

                // Check if vehicle type matches
                if ($ride->ride_type !== ($driver->vehicle->type ?? 'economy')) {
                    return response()->json([
                        'status' => 'error',
                        'message' => 'Vehicle type does not match ride requirement',
                    ], 400);
                }

                // Assign driver to ride
                $ride->update([
                    'driver_id' => $driver->id,
                    'status' => 'accepted',
                    'accepted_at' => now(),
                ]);

                // Update driver status
                $driver->update([
                    'status' => 'on_trip',
                    'is_available' => false,
                ]);

                // Load relations
                $ride->load(['rider', 'stops', 'driver']);

                // Notify Rider
                \App\Events\RideStatusUpdated::dispatch($ride);

                return response()->json([
                    'status' => 'success',
                    'message' => 'Ride accepted successfully',
                    'data' => [
                        'ride' => $ride,
                    ],
                ]);
            });
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to accept ride: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * POST /api/v1/driver/rides/{rideId}/cancel
     * Cancel a ride (driver cancels after accepting)
     */
    public function cancelRide(Request $request, $rideId): JsonResponse
    {
        $driver = auth()->user();

        $ride = Ride::where('id', $rideId)
            ->where('driver_id', $driver->id)
            ->whereIn('status', ['accepted', 'ongoing'])
            ->first();

        if (!$ride) {
            return response()->json([
                'status' => 'error',
                'message' => 'Ride not found or cannot be cancelled',
            ], 404);
        }

        try {
            return DB::transaction(function () use ($ride, $driver, $request) {
                $ride->update([
                    'status' => 'cancelled',
                    'cancelled_by' => 'driver',
                    'cancelled_at' => now(),
                    'cancellation_reason' => $request->reason ?? 'Driver cancelled',
                ]);

                // Reset driver status
                $driver->update([
                    'status' => 'online',
                    'is_available' => true,
                ]);

                $ride->load(['driver']);
                \App\Events\RideStatusUpdated::dispatch($ride);

                return response()->json([
                    'status' => 'success',
                    'message' => 'Ride cancelled successfully',
                ]);
            });
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to cancel ride: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * POST /api/v1/driver/rides/{rideId}/start
     * Start the ride (driver arrives at pickup)
     */
    public function startRide(Request $request, $rideId): JsonResponse
    {
        $driver = auth()->user();

        $ride = Ride::where('id', $rideId)
            ->where('driver_id', $driver->id)
            ->where('status', 'accepted')
            ->first();

        if (!$ride) {
            return response()->json([
                'status' => 'error',
                'message' => 'Ride not found or cannot be started',
            ], 404);
        }

        $ride->update([
            'status' => 'ongoing',
            'started_at' => now(),
        ]);

        $ride->load(['rider', 'stops', 'driver']);
        \App\Events\RideStatusUpdated::dispatch($ride);

        return response()->json([
            'status' => 'success',
            'message' => 'Ride started successfully',
            'data' => [
                'ride' => $ride,
            ],
        ]);
    }

    /**
     * POST /api/v1/driver/rides/{rideId}/complete
     * Complete the ride (driver drops off rider)
     */
    public function completeRide(Request $request, $rideId): JsonResponse
    {
        $driver = auth()->user();

        $ride = Ride::where('id', $rideId)
            ->where('driver_id', $driver->id)
            ->where('status', 'ongoing')
            ->first();

        if (!$ride) {
            return response()->json([
                'status' => 'error',
                'message' => 'Ride not found or cannot be completed',
            ], 404);
        }

        try {
            return DB::transaction(function () use ($ride, $driver) {
                $ride->update([
                    'status' => 'completed',
                    'completed_at' => now(),
                ]);

                // Reset driver status to available
                $driver->update([
                    'status' => 'online',
                    'is_available' => true,
                ]);

                $ride->load(['rider', 'stops', 'driver']);
                \App\Events\RideStatusUpdated::dispatch($ride);

                return response()->json([
                    'status' => 'success',
                    'message' => 'Ride completed successfully',
                    'data' => [
                        'ride' => $ride,
                    ],
                ]);
            });
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to complete ride: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * GET /api/v1/driver/rides/current
     * Get current active ride for driver
     */
    public function getCurrentRide(Request $request): JsonResponse
    {
        $driver = auth()->user();

        $ride = Ride::with(['rider', 'stops'])
            ->where('driver_id', $driver->id)
            ->whereIn('status', ['accepted', 'ongoing'])
            ->first();

        return response()->json([
            'status' => 'success',
            'data' => [
                'ride' => $ride,
                'has_active_ride' => $ride !== null,
            ],
        ]);
    }

    /**
     * GET /api/v1/driver/rides/history
     * Get driver's ride history
     */
    public function getRideHistory(Request $request): JsonResponse
    {
        $driver = auth()->user();

        $rides = Ride::with(['rider', 'stops'])
            ->where('driver_id', $driver->id)
            ->whereIn('status', ['completed', 'cancelled'])
            ->latest()
            ->paginate(20);

        return response()->json([
            'status' => 'success',
            'data' => $rides,
        ]);
    }
}
