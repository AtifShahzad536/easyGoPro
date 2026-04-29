<?php

namespace App\Http\Controllers\Api\Common;

use App\Http\Controllers\Controller;
use App\Models\CarpoolRide;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Throwable;

class CarpoolController extends Controller
{
    /**
     * Publish a new carpool ride
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function publishRide(Request $request): JsonResponse
    {
        try {
            $driver = $request->user();

            if (!$driver) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Unauthorized. Please login first.',
                ], 401);
            }

            // Check if user is a Driver
            if (!$driver instanceof \App\Models\Driver) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Unauthorized. Only drivers can publish rides.',
                ], 403);
            }

            $validator = Validator::make($request->all(), [
                'vehicle_id' => 'nullable|exists:vehicles,id',
                'origin_address' => 'required|string|max:255',
                'origin_lat' => 'required|numeric|between:-90,90',
                'origin_lng' => 'required|numeric|between:-180,180',
                'destination_address' => 'required|string|max:255',
                'destination_lat' => 'required|numeric|between:-90,90',
                'destination_lng' => 'required|numeric|between:-180,180',
                'ride_date' => 'required|date|after_or_equal:today',
                'ride_time' => 'required|date_format:H:i',
                'available_seats' => 'required|integer|min:1|max:10',
                'fare_per_seat' => 'required|numeric|min:0|max:100000',
                'notes' => 'nullable|string|max:1000',
            ], [
                'origin_address.required' => 'Please enter the starting location',
                'destination_address.required' => 'Please enter the destination location',
                'ride_date.required' => 'Please select a ride date',
                'ride_date.after_or_equal' => 'Ride date must be today or in the future',
                'ride_time.required' => 'Please select a ride time',
                'available_seats.required' => 'Please specify available seats',
                'available_seats.min' => 'Minimum 1 seat required',
                'available_seats.max' => 'Maximum 10 seats allowed',
                'fare_per_seat.required' => 'Please specify fare per seat',
                'fare_per_seat.min' => 'Fare cannot be negative',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Validation failed',
                    'errors' => $validator->errors(),
                ], 422);
            }

            // Create departure timestamp
            $departureTimestamp = $request->ride_date . ' ' . $request->ride_time . ':00';

            $ride = CarpoolRide::create([
                'driver_id' => $driver->id,
                'vehicle_id' => $request->vehicle_id,
                'origin_address' => $request->origin_address,
                'origin_lat' => $request->origin_lat,
                'origin_lng' => $request->origin_lng,
                'destination_address' => $request->destination_address,
                'destination_lat' => $request->destination_lat,
                'destination_lng' => $request->destination_lng,
                'ride_date' => $request->ride_date,
                'ride_time' => $request->ride_time,
                'departure_timestamp' => $departureTimestamp,
                'available_seats' => $request->available_seats,
                'fare_per_seat' => $request->fare_per_seat,
                'notes' => $request->notes,
                'status' => 'active',
            ]);

            return response()->json([
                'status' => 'success',
                'message' => 'Carpool ride published successfully',
                'data' => [
                    'ride_id' => $ride->id,
                    'driver_id' => $ride->driver_id,
                    'vehicle_id' => $ride->vehicle_id,
                    'origin' => [
                        'address' => $ride->origin_address,
                        'lat' => $ride->origin_lat,
                        'lng' => $ride->origin_lng,
                    ],
                    'destination' => [
                        'address' => $ride->destination_address,
                        'lat' => $ride->destination_lat,
                        'lng' => $ride->destination_lng,
                    ],
                    'ride_date' => $ride->ride_date->format('Y-m-d'),
                    'ride_time' => $ride->ride_time->format('H:i'),
                    'available_seats' => $ride->available_seats,
                    'fare_per_seat' => $ride->fare_per_seat,
                    'notes' => $ride->notes,
                    'status' => $ride->status,
                    'created_at' => $ride->created_at->toDateTimeString(),
                ],
            ], 201);

        } catch (Throwable $e) {
            Log::error('Carpool ride publish error: ' . $e->getMessage(), [
                'driver_id' => $request->user()?->id,
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json([
                'status' => 'error',
                'message' => 'An error occurred while publishing the ride. Please try again.',
            ], 500);
        }
    }

    /**
     * Get my published carpool rides
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function myRides(Request $request): JsonResponse
    {
        try {
            $driver = $request->user();

            if (!$driver) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Unauthorized. Please login first.',
                ], 401);
            }

            // Check if user is a Driver
            if (!$driver instanceof \App\Models\Driver) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Unauthorized. Only drivers can view their rides.',
                ], 403);
            }

            $rides = CarpoolRide::byDriver($driver->id)
                ->orderBy('ride_date', 'desc')
                ->orderBy('ride_time', 'desc')
                ->get();

            $formattedRides = $rides->map(function ($ride) {
                return [
                    'ride_id' => $ride->id,
                    'origin' => [
                        'address' => $ride->origin_address,
                        'lat' => $ride->origin_lat,
                        'lng' => $ride->origin_lng,
                    ],
                    'destination' => [
                        'address' => $ride->destination_address,
                        'lat' => $ride->destination_lat,
                        'lng' => $ride->destination_lng,
                    ],
                    'ride_date' => $ride->ride_date->format('d M Y'),
                    'ride_time' => $ride->ride_time->format('h:i A'),
                    'available_seats' => $ride->available_seats,
                    'fare_per_seat' => $ride->fare_per_seat,
                    'notes' => $ride->notes,
                    'status' => $ride->status,
                    'created_at' => $ride->created_at->toDateTimeString(),
                ];
            });

            return response()->json([
                'status' => 'success',
                'data' => [
                    'total_rides' => $rides->count(),
                    'rides' => $formattedRides,
                ],
            ], 200);

        } catch (Throwable $e) {
            Log::error('Get my rides error: ' . $e->getMessage());

            return response()->json([
                'status' => 'error',
                'message' => 'An error occurred while fetching rides.',
            ], 500);
        }
    }

    /**
     * Get all available carpool rides (for riders to search)
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function availableRides(Request $request): JsonResponse
    {
        try {
            $validator = Validator::make($request->all(), [
                'origin_lat' => 'nullable|numeric|between:-90,90',
                'origin_lng' => 'nullable|numeric|between:-180,180',
                'destination_lat' => 'nullable|numeric|between:-90,90',
                'destination_lng' => 'nullable|numeric|between:-180,180',
                'ride_date' => 'nullable|date',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Validation failed',
                    'errors' => $validator->errors(),
                ], 422);
            }

            $query = CarpoolRide::active()
                ->where('ride_date', '>=', now()->format('Y-m-d'));

            // Filter by origin location if provided (within 10km radius)
            if ($request->has('origin_lat') && $request->has('origin_lng')) {
                $query->fromOrigin($request->origin_lat, $request->origin_lng, 10);
            }

            // Filter by destination location if provided (within 10km radius)
            if ($request->has('destination_lat') && $request->has('destination_lng')) {
                $query->toDestination($request->destination_lat, $request->destination_lng, 10);
            }

            // Filter by date if provided
            if ($request->has('ride_date')) {
                $query->where('ride_date', $request->ride_date);
            }

            $rides = $query->with('driver:id,full_name,profile_photo')
                ->orderBy('ride_date', 'asc')
                ->orderBy('ride_time', 'asc')
                ->get();

            $formattedRides = $rides->map(function ($ride) {
                return [
                    'ride_id' => $ride->id,
                    'origin' => [
                        'address' => $ride->origin_address,
                        'lat' => $ride->origin_lat,
                        'lng' => $ride->origin_lng,
                    ],
                    'destination' => [
                        'address' => $ride->destination_address,
                        'lat' => $ride->destination_lat,
                        'lng' => $ride->destination_lng,
                    ],
                    'ride_date' => $ride->ride_date->format('d M Y'),
                    'ride_time' => $ride->ride_time->format('h:i A'),
                    'available_seats' => $ride->available_seats,
                    'fare_per_seat' => $ride->fare_per_seat,
                    'notes' => $ride->notes,
                    'driver' => [
                        'driver_id' => $ride->driver->id,
                        'full_name' => $ride->driver->full_name,
                        'profile_photo' => $ride->driver->profile_photo,
                    ],
                    'vehicle' => $ride->vehicle ? [
                        'vehicle_id' => $ride->vehicle->id,
                        'model' => $ride->vehicle->model,
                        'plate_number' => $ride->vehicle->plate_number,
                    ] : null,
                ];
            });

            return response()->json([
                'status' => 'success',
                'data' => [
                    'total_rides' => $rides->count(),
                    'rides' => $formattedRides,
                ],
            ], 200);

        } catch (Throwable $e) {
            Log::error('Get available rides error: ' . $e->getMessage());

            return response()->json([
                'status' => 'error',
                'message' => 'An error occurred while fetching available rides.',
            ], 500);
        }
    }

    /**
     * Cancel a carpool ride
     *
     * @param Request $request
     * @param int $rideId
     * @return JsonResponse
     */
    public function cancelRide(Request $request, $rideId): JsonResponse
    {
        try {
            $driver = $request->user();

            if (!$driver) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Unauthorized. Please login first.',
                ], 401);
            }

            // Check if user is a Driver
            if (!$driver instanceof \App\Models\Driver) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Unauthorized. Only drivers can cancel rides.',
                ], 403);
            }

            $ride = CarpoolRide::byDriver($driver->id)
                ->where('id', $rideId)
                ->first();

            if (!$ride) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Ride not found or you do not have permission to cancel this ride.',
                ], 404);
            }

            if ($ride->status === 'cancelled') {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Ride is already cancelled.',
                ], 400);
            }

            $ride->status = 'cancelled';
            $ride->save();

            return response()->json([
                'status' => 'success',
                'message' => 'Ride cancelled successfully',
                'data' => [
                    'ride_id' => $ride->id,
                    'status' => $ride->status,
                ],
            ], 200);

        } catch (Throwable $e) {
            Log::error('Cancel ride error: ' . $e->getMessage());

            return response()->json([
                'status' => 'error',
                'message' => 'An error occurred while cancelling the ride.',
            ], 500);
        }
    }

    /**
     * Edit/Update a carpool ride
     *
     * @param Request $request
     * @param int $rideId
     * @return JsonResponse
     */
    public function editRide(Request $request, $rideId): JsonResponse
    {
        try {
            $driver = $request->user();

            if (!$driver) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Unauthorized. Please login first.',
                ], 401);
            }

            // Check if user is a Driver
            if (!$driver instanceof \App\Models\Driver) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Unauthorized. Only drivers can edit rides.',
                ], 403);
            }

            $ride = CarpoolRide::byDriver($driver->id)
                ->where('id', $rideId)
                ->first();

            if (!$ride) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Ride not found or you do not have permission to edit this ride.',
                ], 404);
            }

            if ($ride->status === 'cancelled') {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Cannot edit a cancelled ride.',
                ], 400);
            }

            $validator = Validator::make($request->all(), [
                'vehicle_id' => 'sometimes|exists:vehicles,id',
                'origin_address' => 'sometimes|string|max:255',
                'origin_lat' => 'sometimes|numeric|between:-90,90',
                'origin_lng' => 'sometimes|numeric|between:-180,180',
                'destination_address' => 'sometimes|string|max:255',
                'destination_lat' => 'sometimes|numeric|between:-90,90',
                'destination_lng' => 'sometimes|numeric|between:-180,180',
                'ride_date' => 'sometimes|date|after_or_equal:today',
                'ride_time' => 'sometimes|date_format:H:i',
                'available_seats' => 'sometimes|integer|min:1|max:10',
                'fare_per_seat' => 'sometimes|numeric|min:0|max:100000',
                'notes' => 'nullable|string|max:1000',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Validation failed',
                    'errors' => $validator->errors(),
                ], 422);
            }

            // Update only provided fields
            if ($request->has('vehicle_id')) $ride->vehicle_id = $request->vehicle_id;
            if ($request->has('origin_address')) $ride->origin_address = $request->origin_address;
            if ($request->has('origin_lat')) $ride->origin_lat = $request->origin_lat;
            if ($request->has('origin_lng')) $ride->origin_lng = $request->origin_lng;
            if ($request->has('destination_address')) $ride->destination_address = $request->destination_address;
            if ($request->has('destination_lat')) $ride->destination_lat = $request->destination_lat;
            if ($request->has('destination_lng')) $ride->destination_lng = $request->destination_lng;
            if ($request->has('ride_date')) $ride->ride_date = $request->ride_date;
            if ($request->has('ride_time')) $ride->ride_time = $request->ride_time;
            if ($request->has('available_seats')) $ride->available_seats = $request->available_seats;
            if ($request->has('fare_per_seat')) $ride->fare_per_seat = $request->fare_per_seat;
            if ($request->has('notes')) $ride->notes = $request->notes;
            
            // Update departure timestamp if date or time changed
            if ($request->has('ride_date') || $request->has('ride_time')) {
                $ride->departure_timestamp = $ride->ride_date->format('Y-m-d') . ' ' . $ride->ride_time . ':00';
            }

            $ride->save();

            return response()->json([
                'status' => 'success',
                'message' => 'Ride updated successfully',
                'data' => [
                    'ride_id' => $ride->id,
                    'vehicle_id' => $ride->vehicle_id,
                    'origin' => [
                        'address' => $ride->origin_address,
                        'lat' => $ride->origin_lat,
                        'lng' => $ride->origin_lng,
                    ],
                    'destination' => [
                        'address' => $ride->destination_address,
                        'lat' => $ride->destination_lat,
                        'lng' => $ride->destination_lng,
                    ],
                    'ride_date' => $ride->ride_date->format('Y-m-d'),
                    'ride_time' => $ride->ride_time->format('H:i'),
                    'available_seats' => $ride->available_seats,
                    'fare_per_seat' => $ride->fare_per_seat,
                    'notes' => $ride->notes,
                    'status' => $ride->status,
                    'updated_at' => $ride->updated_at->toDateTimeString(),
                ],
            ], 200);

        } catch (Throwable $e) {
            Log::error('Edit ride error: ' . $e->getMessage());

            return response()->json([
                'status' => 'error',
                'message' => 'An error occurred while updating the ride.',
            ], 500);
        }
    }

    /**
     * Delete a carpool ride permanently
     *
     * @param Request $request
     * @param int $rideId
     * @return JsonResponse
     */
    public function deleteRide(Request $request, $rideId): JsonResponse
    {
        try {
            $driver = $request->user();

            if (!$driver) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Unauthorized. Please login first.',
                ], 401);
            }

            // Check if user is a Driver
            if (!$driver instanceof \App\Models\Driver) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Unauthorized. Only drivers can delete rides.',
                ], 403);
            }

            $ride = CarpoolRide::byDriver($driver->id)
                ->where('id', $rideId)
                ->first();

            if (!$ride) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Ride not found or you do not have permission to delete this ride.',
                ], 404);
            }

            // Store ride info before deletion for response
            $deletedRideId = $ride->id;
            $ride->delete();

            return response()->json([
                'status' => 'success',
                'message' => 'Ride deleted permanently',
                'data' => [
                    'ride_id' => $deletedRideId,
                    'deleted' => true,
                ],
            ], 200);

        } catch (Throwable $e) {
            Log::error('Delete ride error: ' . $e->getMessage());

            return response()->json([
                'status' => 'error',
                'message' => 'An error occurred while deleting the ride.',
            ], 500);
        }
    }
}
