<?php

namespace App\Http\Controllers\Api\Rider;

use App\Http\Controllers\Controller;
use App\Models\Ride;
use App\Models\RideStop;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class RideBookingController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth:sanctum');
    }

    /**
     * POST /api/v1/rider/estimate-fare
     * Get fare estimates for all vehicle types before booking
     */
    public function estimateFare(Request $request): JsonResponse
    {
        $rider = auth()->user();

        $validator = Validator::make($request->all(), [
            // Pickup location
            'pickup.latitude' => 'required|numeric|between:-90,90',
            'pickup.longitude' => 'required|numeric|between:-180,180',
            
            // Destination location
            'destination.latitude' => 'required|numeric|between:-90,90',
            'destination.longitude' => 'required|numeric|between:-180,180',
            'distance' => 'required|numeric|',
            'time' =>'required|numeric|',
            
            // Optional stops (max 4)
            'stops' => 'nullable|array|max:4',
            'stops.*.latitude' => 'required_with:stops|numeric|between:-90,90',
            'stops.*.longitude' => 'required_with:stops|numeric|between:-180,180',
        ],[

            'distance.numeric' => 'Distance must be a valid number',
            'time.numeric' => 'Time must be a valid number',
            'stops.max' => 'Maximum 4 stops allowed',

        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
            ], 422);
        }

        // Calculate distance (mock for now - integrate Google Distance Matrix API)
        // Add stops distance
        $stopsCount = $request->has('stops') ? count($request->stops) : 0;
        
        // Calculate fares for all vehicle types
        $estimates = $this->getAllVehicleEstimates($request->distance, $stopsCount);

        return response()->json([
            'status' => 'success',
            'data' => [
                'distance_km' => round($request->distance, 2),
                'duration_minutes' => round($request->distance * 3), // Mock: 3 min per km
                'total_stops' => $stopsCount,
                'max_stops_allowed' => 4,
                'vehicle_options' => $estimates,
            ],
        ]);
    }

    /**
     * POST /api/v1/rider/standard-book-ride
     * Book a standard ride with pickup, destination and optional stops (max 4)
     */
    public function standardBookRide(Request $request): JsonResponse
    {
        $rider = auth()->user();

        $validator = Validator::make($request->all(), [
            // Pickup location
            'pickup.place_name' => 'required|string|max:255',
            'pickup.latitude' => 'required|numeric|between:-90,90',
            'pickup.longitude' => 'required|numeric|between:-180,180',
            
            // Destination location
            'destination.place_name' => 'required|string|max:255',
            'destination.latitude' => 'required|numeric|between:-90,90',
            'destination.longitude' => 'required|numeric|between:-180,180',
            
            // Ride distance and time
            'distance' => 'required|numeric|min:0',
            'duration' => 'required|numeric|min:0',
            
            // Optional stops (max 4)
            'stops' => 'nullable|array|max:4',
            'stops.*.place_name' => 'required_with:stops|string|max:255',
            'stops.*.latitude' => 'required_with:stops|numeric|between:-90,90',
            'stops.*.longitude' => 'required_with:stops|numeric|between:-180,180',
            
            // Ride details
            'ride_type' => 'required|in:bike,auto,economy,business,car_pool',
            'payment_method' => 'required|in:cash',
            'estimate_fare' => 'required|numeric|min:0',
        ], [
            'pickup.place_name.required' => 'Pickup place name is required',
            'pickup.latitude.required' => 'Pickup latitude is required',
            'pickup.longitude.required' => 'Pickup longitude is required',
            'destination.place_name.required' => 'Destination place name is required',
            'destination.latitude.required' => 'Destination latitude is required',
            'destination.longitude.required' => 'Destination longitude is required',
            'distance.required' => 'Distance is required',
            'duration.required' => 'Duration is required',
            'ride_type.required' => 'Ride type is required',
            'ride_type.in' => 'Invalid ride type. Must be bike, auto, economy, business, or car_pool',
            'payment_method.required' => 'Payment method is required',
            'estimate_fare.required' => 'Estimated fare is required',
            'stops.max' => 'Maximum 4 stops allowed',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
            ], 422);
        }

        try {
            return DB::transaction(function () use ($request, $rider) {
                // Create the ride
                $ride = Ride::create([
                    'rider_id' => $rider->id,
                    'driver_id' => null, // Will be assigned later
                    'pickup_place_name' => $request->pickup['place_name'],
                    'pickup_lat' => $request->pickup['latitude'],
                    'pickup_lng' => $request->pickup['longitude'],
                    'destination_place_name' => $request->destination['place_name'],
                    'destination_lat' => $request->destination['latitude'],
                    'destination_lng' => $request->destination['longitude'],
                    'status' => 'searching',
                    'ride_type' => $request->ride_type,
                    'payment_method' => $request->payment_method,
                    'estimated_fare' => $request->estimate_fare,
                    'distance_km' => $request->distance,
                    'duration_minutes' => $request->duration,
                ]);

                // Add stops if any
                if ($request->has('stops') && count($request->stops) > 0) {
                    foreach ($request->stops as $index => $stop) {
                        RideStop::create([
                            'ride_id' => $ride->id,
                            'stop_order' => $index + 1,
                            'place_name' => $stop['place_name'],
                            'latitude' => $stop['latitude'],
                            'longitude' => $stop['longitude'],
                            'status' => 'pending',
                        ]);
                    }
                }

                // Load stops relation
                $ride->load('stops');

                // Broadcast the ride request to drivers
                \App\Events\RideRequested::dispatch($ride);

                return response()->json([
                    'status' => 'success',
                    'message' => 'Ride booked successfully',
                    'data' => [
                        'ride_id' => $ride->id,
                        'ride' => $ride,
                        'total_stops' => $ride->stops->count(),
                        'max_stops' => 4,
                        'status' => 'searching_driver',
                        'estimated_fare' => $ride->estimated_fare,
                    ],
                ], 201);
            });
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to book ride: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * GET /api/v1/rider/rides
     * Get all rides for rider
     */
    public function getRides(Request $request): JsonResponse
    {
        $rider = auth()->user();

        $status = $request->query('status'); // Optional: filter by status

        $query = Ride::with(['driver', 'stops'])
            ->where('rider_id', $rider->id);

        if ($status) {
            $query->where('status', $status);
        }

        $rides = $query->latest()->get();

        return response()->json([
            'status' => 'success',
            'data' => $rides,
        ]);
    }

    /**
     * GET /api/v1/rider/rides/active
     * Get active ride for rider
     */
    public function getActiveRide(Request $request): JsonResponse
    {
        $rider = auth()->user();

        $ride = Ride::with(['driver', 'stops'])
            ->where('rider_id', $rider->id)
            ->whereIn('status', ['searching', 'accepted', 'ongoing'])
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
     * GET /api/v1/rider/rides/history
     * Get rider's ride history (completed/cancelled)
     */
    public function getRideHistory(Request $request): JsonResponse
    {
        $rider = auth()->user();

        $rides = Ride::with(['driver', 'stops'])
            ->where('rider_id', $rider->id)
            ->whereIn('status', ['completed', 'cancelled'])
            ->latest()
            ->get();

        return response()->json([
            'status' => 'success',
            'data' => $rides,
        ]);
    }

    /**
     * GET /api/v1/rider/rides/{rideId}
     * Get ride details with stops
     */
    public function getRideDetails($rideId): JsonResponse
    {
        $rider = auth()->user();

        $ride = Ride::with(['stops', 'driver'])
            ->where('id', $rideId)
            ->where('rider_id', $rider->id)
            ->first();

        if (!$ride) {
            return response()->json([
                'status' => 'error',
                'message' => 'Ride not found',
            ], 404);
        }

        return response()->json([
            'status' => 'success',
            'data' => $ride,
        ]);
    }

    /**
     * POST /api/v1/rider/rides/{rideId}/cancel
     * Cancel a ride
     */
    public function cancelRide($rideId): JsonResponse
    {
        $rider = auth()->user();

        $ride = Ride::where('id', $rideId)
            ->where('rider_id', $rider->id)
            ->whereIn('status', ['searching', 'accepted'])
            ->first();

        if (!$ride) {
            return response()->json([
                'status' => 'error',
                'message' => 'Ride not found or cannot be cancelled',
            ], 404);
        }

        $ride->update(['status' => 'cancelled']);

        // Broadcast cancellation so driver app removes it
        \App\Events\RideCancelled::dispatch($ride);

        return response()->json([
            'status' => 'success',
            'message' => 'Ride cancelled successfully',
        ]);
    }

    /**
     * Helper: Calculate estimated fare
     */
    private function calculateFare(Request $request): float
    {
        $distanceKm = $this->calculateDistance(
            $request->pickup['latitude'],
            $request->pickup['longitude'],
            $request->destination['latitude'],
            $request->destination['longitude']
        );
        
        $stopsCount = $request->has('stops') ? count($request->stops) : 0;
        
        return $this->calculateFareForVehicle($request->ride_type, $distanceKm, $stopsCount);
    }

    /**
     * Helper: Calculate distance between two points (Haversine formula)
     * TODO: Replace with Google Distance Matrix API for accurate road distance
     */
    private function calculateDistance(float $lat1, float $lng1, float $lat2, float $lng2): float
    {
        $earthRadius = 6371; // km

        $latDelta = deg2rad($lat2 - $lat1);
        $lngDelta = deg2rad($lng2 - $lng1);

        $a = sin($latDelta / 2) * sin($latDelta / 2) +
            cos(deg2rad($lat1)) * cos(deg2rad($lat2)) *
            sin($lngDelta / 2) * sin($lngDelta / 2);

        $c = 2 * atan2(sqrt($a), sqrt(1 - $a));

        return $earthRadius * $c;
    }

    /**
     * Helper: Get fare estimates for all vehicle types
     */
    private function getAllVehicleEstimates(float $distanceKm, int $stopsCount): array
    {
        $vehicles = [
            [
                'type' => 'bike',
                'name' => 'Bike',
                'capacity' => 1,
                'base_fare' => 30,
                'per_km_rate' => 8,
               
            ],
            [
                'type' => 'auto',
                'name' => 'Auto',
                'capacity' => 3,
                'base_fare' => 50,
                'per_km_rate' => 15,
                
            ],
            [
                'type' => 'economy',
                'name' => 'Economy Car',
                'capacity' => 4,
                'base_fare' => 80,
                'per_km_rate' => 25,
                
            ],
            [
                'type' => 'business',
                'name' => 'Comfort Car',
                'capacity' => 4,
                'base_fare' => 150,
                'per_km_rate' => 40,
           

            ],
            [
                'type' => 'car_pool',
                'name' => 'Carpool',
                'capacity' => 4,
                'base_fare' => 40,
                'per_km_rate' => 12,
                
            ],
        ];

        $estimates = [];
        foreach ($vehicles as $vehicle) {
            $distanceFare = $distanceKm * $vehicle['per_km_rate'];
            $stopCharges = $stopsCount * 20; // Rs. 20 per stop
            $totalFare = $vehicle['base_fare'] + $distanceFare + $stopCharges;
            
            // Surge pricing (mock - can be dynamic based on demand)
            $surgeMultiplier = 1.0;
            $finalFare = round($totalFare * $surgeMultiplier);

            $estimates[] = [
                'type' => $vehicle['type'],
                'name' => $vehicle['name'],
                'capacity' => $vehicle['capacity'],
                
                'fare' => [
                    'estimated' => $finalFare,
                    'base_fare' => $vehicle['base_fare'],
                    'distance_fare' => round($distanceFare, 2),
                    'stop_charges' => $stopCharges,
                    'surge_multiplier' => $surgeMultiplier,
                ],
                'eta_minutes' => $this->calculateETA($vehicle['type']),
                'is_available' => true, // Can check vehicle availability
            ];
        }

        return $estimates;
    }

    /**
     * Helper: Calculate fare for specific vehicle type
     */
    private function calculateFareForVehicle(string $rideType, float $distanceKm, int $stopsCount): float
    {
        $rates = [
            'bike' => ['base' => 30, 'per_km' => 8],
            'auto' => ['base' => 50, 'per_km' => 15],
            'economy' => ['base' => 80, 'per_km' => 25],
            'business' => ['base' => 150, 'per_km' => 40],
            'car_pool' => ['base' => 40, 'per_km' => 12],
        ];

        $rate = $rates[$rideType] ?? $rates['economy'];
        $distanceFare = $distanceKm * $rate['per_km'];
        $stopCharges = $stopsCount * 20;

        return round($rate['base'] + $distanceFare + $stopCharges);
    }

    /**
     * Helper: Calculate ETA for vehicle type (mock)
     * TODO: Get real ETA from nearby drivers
     */
    private function calculateETA(string $vehicleType): int
    {
        $etas = [
            'bike' => 3,
            'auto' => 5,
            'economy' => 4,
            'business' => 6,
            'car_pool' => 8,
        ];

        return $etas[$vehicleType] ?? 5;
    }
}
