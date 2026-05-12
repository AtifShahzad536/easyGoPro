<?php

namespace App\Http\Controllers\Api\Driver;

use App\Http\Controllers\Controller;
use App\Models\Ride;
use App\Models\Driver;
use App\Models\UserLocation;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use App\Models\RideDecline;
use Exception;

class DriverRideController extends Controller
{
    /**
     * GET /api/v1/driver/rides/available
     * Fetch rides searching for drivers near driver's location
     */
    public function getAvailableRides(Request $request): JsonResponse
    {
        try {
            $driver = auth()->user();

            if (!$driver || !$driver->vehicle) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Driver vehicle details not found.'
                ], 404);
            }

            // Get driver's current location from UserLocation table
            $driverLocation = UserLocation::where('user_id', $driver->id)
                ->where('role', 'driver')
                ->first();

            if (!$driverLocation) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Driver location not found. Please update your location first.'
                ], 400);
            }

            $lat = $driverLocation->latitude;
            $lng = $driverLocation->longitude;
            $radius = 10; // Increased to 10km for better visibility during testing

            // Get rides that match driver's vehicle type, are searching, 
            // were created recently (last 30 minutes) AND are within radius
            // AND have NOT been declined by this driver
            $rides = Ride::with(['rider', 'stops'])
                ->select('*')
                ->selectRaw("( 6371 * acos( cos( radians(?) ) * cos( radians( pickup_lat ) ) * cos( radians( pickup_lng ) - radians(?) ) + sin( radians(?) ) * sin( radians( pickup_lat ) ) ) ) AS distance", [$lat, $lng, $lat])
                ->where('status', 'searching')
                ->where('ride_type', $driver->vehicle->type)
                ->whereNull('driver_id')
                ->where('created_at', '>=', now()->subMinutes(1))
                ->whereNotExists(function ($query) use ($driver) {
                    $query->select(DB::raw(1))
                        ->from('ride_declines')
                        ->whereColumn('ride_declines.ride_id', 'rides.id')
                        ->where('ride_declines.driver_id', $driver->id);
                })
                ->having('distance', '<=', $radius)
                ->orderBy('created_at', 'desc') // Newest rides first (Websocket feel)
                ->orderBy('distance', 'asc')
                ->get();

            return response()->json([
                'status' => 'success',
                'data' => [
                    'rides' => $rides,
                    'count' => $rides->count(),
                    'driver_location' => [
                        'lat' => $lat,
                        'lng' => $lng
                    ]
                ],
            ]);
        } catch (Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to fetch available rides.',
                'error' => config('app.debug') ? $e->getMessage() : null
            ], 500);
        }
    }

    /**
     * GET /api/v1/driver/rides/scheduled
     * Fetch available scheduled rides (Reserve / Two-Way)
     */
    public function getScheduledRides(Request $request): JsonResponse
    {
        try {
            $driver = auth()->user();

            if (!$driver || !$driver->vehicle) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Driver vehicle details not found.'
                ], 404);
            }

            // Get rides that match driver's vehicle type and are scheduled
            $rides = Ride::with(['rider'])
                ->where('status', 'scheduled')
                ->where('ride_type', $driver->vehicle->type)
                ->whereNull('driver_id')
                ->where('scheduled_at', '>', now())
                ->orderBy('scheduled_at', 'asc')
                ->get();

            return response()->json([
                'status' => 'success',
                'data' => [
                    'rides' => $rides,
                    'count' => $rides->count(),
                ],
            ]);
        } catch (Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to fetch scheduled rides.',
                'error' => config('app.debug') ? $e->getMessage() : null
            ], 500);
        }
    }

    /**
     * PATCH /api/v1/driver/rides/{rideId}/accept
     * Driver accepts a ride request
     */
    public function acceptRide(Request $request, $rideId): JsonResponse
    {
        try {
            $driver = auth()->user();

            return DB::transaction(function () use ($rideId, $driver) {
                // Check if ride is still available
                $ride = Ride::lockForUpdate()
                    ->where('id', $rideId)
                    ->where('status', 'searching')
                    ->first();

                if (!$ride) {
                    return response()->json([
                        'status' => 'error',
                        'message' => 'Ride is no longer available or already accepted by someone else.',
                    ], 409);
                }

                // Check if driver is already on another ride
                if ($driver->status === 'on_trip') {
                    return response()->json([
                        'status' => 'error',
                        'message' => 'You are already on an active ride.',
                    ], 403);
                }

                // Update Ride
                $ride->update([
                    'driver_id' => $driver->id,
                    'status' => 'accepted',
                    'accepted_at' => now(),
                ]);

                // Update Driver status
                $driver->update([
                    'status' => 'on_trip',
                    'is_available' => false,
                ]);

                return response()->json([
                    'status' => 'success',
                    'message' => 'Ride accepted successfully.',
                    'data' => ['ride' => $ride->load(['rider', 'stops'])]
                ]);
            });
        } catch (Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'An error occurred while accepting the ride.',
                'error' => config('app.debug') ? $e->getMessage() : null
            ], 500);
        }
    }

    /**
     * PATCH /api/v1/driver/rides/{rideId}/decline
     * Driver declines a ride request (won't show in available list anymore)
     */
    public function declineRide(Request $request, $rideId): JsonResponse
    {
        try {
            $driver = auth()->user();
            
            // Record the decline
            RideDecline::updateOrCreate([
                'ride_id' => $rideId,
                'driver_id' => $driver->id
            ]);

            return response()->json([
                'status' => 'success',
                'message' => 'Ride declined successfully. It will no longer appear in your list.'
            ]);
        } catch (Exception $e) {
            return response()->json([
                'status' => 'error', 
                'message' => 'Failed to decline ride.',
                'error' => config('app.debug') ? $e->getMessage() : null
            ], 500);
        }
    }

    /**
     * PATCH /api/v1/driver/rides/{rideId}/arrived
     * Driver notifies that they have arrived at the pickup location
     */
    public function arrivedAtPickup(Request $request, $rideId): JsonResponse
    {
        try {
            $driver = auth()->user();
            $ride = Ride::where('id', $rideId)
                ->where('driver_id', $driver->id)
                ->where('status', 'accepted')
                ->first();

            if (!$ride) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Ride not found or cannot be marked as arrived.'
                ], 404);
            }

            $ride->update([
                'status' => 'driver_arrived',
                'driver_arrived_at' => now(),
            ]);

            return response()->json([
                'status' => 'success',
                'message' => 'Rider notified. You have arrived at the pickup location.',
                'data' => $ride
            ]);
        } catch (Exception $e) {
            return response()->json([
                'status' => 'error', 
                'message' => 'Failed to update arrival status.',
                'error' => config('app.debug') ? $e->getMessage() : null
            ], 500);
        }
    }

    /**
     * PATCH /api/v1/driver/rides/{rideId}/start
     * Driver starts the ride after picking up the rider
     */
    public function startRide(Request $request, $rideId): JsonResponse
    {
        try {
            $driver = auth()->user();

            $ride = Ride::where('id', $rideId)
                ->where('driver_id', $driver->id)
                ->where('status', 'accepted')
                ->first();

            if (!$ride) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Ride not found or cannot be started (Must be in accepted status).',
                ], 404);
            }

            $ride->update([
                'status' => 'ongoing',
                'started_at' => now(),
            ]);

            return response()->json([
                'status' => 'success',
                'message' => 'Ride started successfully.',
                'data' => ['ride' => $ride->load(['rider', 'stops'])]
            ]);
        } catch (Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to start the ride.',
                'error' => config('app.debug') ? $e->getMessage() : null
            ], 500);
        }
    }

    /**
     * PATCH /api/v1/driver/rides/{rideId}/complete
     * Driver completes the trip
     */
    public function completeRide(Request $request, $rideId): JsonResponse
    {
        try {
            $driver = auth()->user();

            $ride = Ride::where('id', $rideId)
                ->where('driver_id', $driver->id)
                ->where('status', 'ongoing')
                ->first();

            if (!$ride) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Active ride not found.',
                ], 404);
            }

            return DB::transaction(function () use ($ride, $driver) {
                $ride->update([
                    'status' => 'completed',
                    'completed_at' => now(),
                ]);

                $driver->update([
                    'status' => 'online',
                    'is_available' => true,
                ]);

                return response()->json([
                    'status' => 'success',
                    'message' => 'Ride completed successfully.',
                    'data' => ['ride' => $ride->load(['rider', 'stops'])]
                ]);
            });
        } catch (Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Error completing the ride.',
                'error' => config('app.debug') ? $e->getMessage() : null
            ], 500);
        }
    }

    /**
     * PATCH /api/v1/driver/rides/{rideId}/rate
     * Driver rates the rider
     */
    public function rateRide(Request $request, $rideId): JsonResponse
    {
        try {
            $validator = Validator::make($request->all(), [
                'rating' => 'required|integer|min:1|max:5',
                'review' => 'nullable|string|max:500',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Invalid rating data.',
                    'errors' => $validator->errors()
                ], 422);
            }

            $driver = auth()->user();
            $ride = Ride::where('id', $rideId)
                ->where('driver_id', $driver->id)
                ->where('status', 'completed')
                ->first();

            if (!$ride) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Completed ride not found to rate.',
                ], 404);
            }

            $ride->update([
                'rider_rating' => $request->rating,
                'rider_review' => $request->review,
            ]);

            return response()->json([
                'status' => 'success',
                'message' => 'Rider rated successfully.',
                'data' => [
                    'rating' => $request->rating,
                    'review' => $request->review
                ]
            ]);
        } catch (Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to submit rating.',
                'error' => config('app.debug') ? $e->getMessage() : null
            ], 500);
        }
    }

    /**
     * GET /api/v1/driver/rides/current
     * Get the driver's currently active ride
     */
    public function getCurrentRide(Request $request): JsonResponse
    {
        try {
            $driver = auth()->user();
            $ride = Ride::with(['rider', 'stops'])
                ->where('driver_id', $driver->id)
                ->whereIn('status', ['accepted', 'driver_arrived', 'ongoing'])
                ->first();

            return response()->json([
                'status' => 'success',
                'data' => [
                    'has_active_ride' => $ride !== null,
                    'ride' => $ride
                ]
            ]);
        } catch (Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to fetch current ride.',
                'error' => config('app.debug') ? $e->getMessage() : null
            ], 500);
        }
    }

    /**
     * GET /api/v1/driver/rides/history
     * Get completed and cancelled rides history
     */
    public function getRideHistory(Request $request): JsonResponse
    {
        try {
            $driver = auth()->user();
            $rides = Ride::where('driver_id', $driver->id)
                ->whereIn('status', ['completed', 'cancelled'])
                ->with(['rider', 'stops'])
                ->latest()
                ->get();

            return response()->json([
                'status' => 'success',
                'data' => $rides
            ]);
        } catch (Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to fetch ride history.',
                'error' => config('app.debug') ? $e->getMessage() : null
            ], 500);
        }
    }

    /**
     * PATCH /api/v1/driver/rides/{rideId}/cancel
     * Driver cancels the accepted ride
     */
    public function cancelRide(Request $request, $rideId): JsonResponse
    {
        try {
            $driver = auth()->user();
            $ride = Ride::where('id', $rideId)
                ->where('driver_id', $driver->id)
                ->whereIn('status', ['accepted','ongoing', 'driver_arrived'])
                ->first();

            if (!$ride) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Ride not found or cannot be cancelled.'
                ], 404);
            }

            return DB::transaction(function () use ($ride, $driver, $request) {
                $ride->update([
                    'status' => 'cancelled',
                    'cancelled_at' => now(),
                    'cancelled_by' => 'driver',
                    'cancellation_reason' => $request->reason ?? 'Cancelled by driver'
                ]);

                // Reset driver status so they can take another ride
                $driver->update([
                    'status' => 'online',
                    'is_available' => true,
                ]);

                return response()->json([
                    'status' => 'success',
                    'message' => 'Ride cancelled successfully. You are now available for other rides.'
                ]);
            });
        } catch (Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to cancel ride.',
                'error' => config('app.debug') ? $e->getMessage() : null
            ], 500);
        }
    }
}
