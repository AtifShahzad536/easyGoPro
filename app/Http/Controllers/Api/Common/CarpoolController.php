<?php

namespace App\Http\Controllers\Api\Common;

use App\Http\Controllers\Controller;
use App\Models\CarpoolRide;
use App\Models\Driver;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Exception;

class CarpoolController extends Controller
{
    /**
     * Publish a new carpool ride
     */
    public function publishRide(Request $request): JsonResponse
    {
        try {
            $driver = $request->user();

            if (!$driver instanceof Driver) {
                return response()->json(['status' => 'error', 'message' => 'Only drivers can publish rides.'], 403);
            }

            $validator = Validator::make($request->all(), [
                'origin_address' => 'required|string|max:255',
                'origin_lat' => 'required|numeric',
                'origin_lng' => 'required|numeric',
                'destination_address' => 'required|string|max:255',
                'destination_lat' => 'required|numeric',
                'destination_lng' => 'required|numeric',
                'ride_date' => 'required|date|after_or_equal:today',
                'ride_time' => 'required|date_format:H:i',
                'available_seats' => 'required|integer|min:1',
                'fare_per_seat' => 'required|numeric|min:0',
            ]);

            if ($validator->fails()) {
                return response()->json(['status' => 'error', 'errors' => $validator->errors()], 422);
            }

            $ride = CarpoolRide::create(array_merge($request->all(), [
                'driver_id' => $driver->id,
                'departure_timestamp' => $request->ride_date . ' ' . $request->ride_time,
                'status' => 'active'
            ]));

            return response()->json([
                'status' => 'success',
                'message' => 'Carpool ride published.',
                'data' => $ride
            ], 201);

        } catch (Exception $e) {
            Log::error('Carpool Publish Error: ' . $e->getMessage());
            return response()->json(['status' => 'error', 'message' => 'Failed to publish ride.'], 500);
        }
    }

    /**
     * Get authenticated driver's rides
     */
    public function myRides(Request $request): JsonResponse
    {
        try {
            $driver = $request->user();
            $rides = CarpoolRide::where('driver_id', $driver->id)->latest()->get();

            return response()->json(['status' => 'success', 'data' => $rides]);
        } catch (Exception $e) {
            return response()->json(['status' => 'error', 'message' => 'Failed to fetch rides.'], 500);
        }
    }

    /**
     * Edit an existing carpool ride
     */
    public function editRide(Request $request, $rideId): JsonResponse
    {
        try {
            $driver = $request->user();
            $ride = CarpoolRide::where('id', $rideId)->where('driver_id', $driver->id)->first();

            if (!$ride) {
                return response()->json(['status' => 'error', 'message' => 'Ride not found.'], 404);
            }

            $ride->update($request->all());

            return response()->json([
                'status' => 'success',
                'message' => 'Ride updated successfully.',
                'data' => $ride
            ]);
        } catch (Exception $e) {
            return response()->json(['status' => 'error', 'message' => 'Failed to update ride.'], 500);
        }
    }

    /**
     * Cancel a carpool ride
     */
    public function cancelRide(Request $request, $rideId): JsonResponse
    {
        try {
            $driver = $request->user();
            $ride = CarpoolRide::where('id', $rideId)->where('driver_id', $driver->id)->first();

            if (!$ride) {
                return response()->json(['status' => 'error', 'message' => 'Ride not found.'], 404);
            }

            $ride->update(['status' => 'cancelled']);

            return response()->json(['status' => 'success', 'message' => 'Ride cancelled.']);
        } catch (Exception $e) {
            return response()->json(['status' => 'error', 'message' => 'Error cancelling ride.'], 500);
        }
    }

    /**
     * Delete a carpool ride
     */
    public function deleteRide(Request $request, $rideId): JsonResponse
    {
        try {
            $driver = $request->user();
            $ride = CarpoolRide::where('id', $rideId)->where('driver_id', $driver->id)->first();

            if (!$ride) {
                return response()->json(['status' => 'error', 'message' => 'Ride not found.'], 404);
            }

            $ride->delete();
            return response()->json(['status' => 'success', 'message' => 'Ride deleted successfully.']);
        } catch (Exception $e) {
            return response()->json(['status' => 'error', 'message' => 'Error deleting ride.'], 500);
        }
    }
}
