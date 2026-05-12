<?php

namespace App\Http\Controllers\Api\Rider;

use App\Http\Controllers\Controller;
use App\Models\Ride;
use App\Models\RideStop;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Exception;

class RideBookingController extends Controller
{
    /**
     * POST /api/v1/rider/estimate-fare
     * Estimate fare for standard or two-way ride
     */
    public function estimateFare(Request $request): JsonResponse
    {
        try {
            $request->merge([
                'booking_type' => $request->booking_type ?? 'standard',
            ]);

            $rules = [
                'booking_type' => 'required|string|in:standard,two_way',
            ];

            if ($request->booking_type === 'two_way') {
                $rules = array_merge($rules, [
                    'go_trip.pickup' => 'required|array',
                    'go_trip.destination' => 'required|array',
                    'go_trip.distance' => 'nullable',
                    'return_trip.pickup' => 'required|array',
                    'return_trip.destination' => 'required|array',
                    'return_trip.distance' => 'nullable',
                ]);
            } else {
                $rules = array_merge($rules, [
                    'pickup' => 'required|array',
                    'destination' => 'required|array',
                    'distance' => 'nullable',
                    'time' => 'nullable',
                    'stops' => 'nullable|array',
                ]);
            }

            $validator = Validator::make($request->all(), $rules);

            if ($validator->fails()) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Validation failed',
                    'errors' => $validator->errors()
                ], 422);
            }

            // --- Multi-Vehicle Configuration ---
            $vehicleConfigs = [
                [
                    'type' => 'bike',
                    'name' => 'Bike',
                    'capacity' => 1,
                    'base_fare' => 30,
                    'per_km' => 8,
                    'eta_minutes' => 3
                ],
                [
                    'type' => 'economy',
                    'name' => 'Economy Car',
                    'capacity' => 4,
                    'base_fare' => 80,
                    'per_km' => 25,
                    'eta_minutes' => 4
                ],
                [
                    'type' => 'auto',
                    'name' => 'Auto',
                    'capacity' => 4,
                    'base_fare' => 60,
                    'per_km' => 20,
                    'eta_minutes' => 4
                ],
                [
                    'type' => 'business',
                    'name' => 'Comfort Car',
                    'capacity' => 4,
                    'base_fare' => 150,
                    'per_km' => 40,
                    'eta_minutes' => 6
                ]
            ];

            if ($request->booking_type === 'two_way') {
                // Two-way logic: Summing up estimates for both trips
                $options = [];
                foreach ($vehicleConfigs as $config) {
                    $goFare = $this->calculateDetailedFare($config, $request->go_trip);
                    $returnFare = $this->calculateDetailedFare($config, $request->return_trip);
                    
                    $subTotal = $goFare['estimated'] + $returnFare['estimated'];
                    $discount = $subTotal * 0.10;

                    $options[] = [
                        'type' => $config['type'],
                        'name' => $config['name'],
                        'capacity' => $config['capacity'],
                        'fare' => [
                            'estimated' => round($subTotal - $discount),
                            'go_trip_fare' => $goFare['estimated'],
                            'return_trip_fare' => $returnFare['estimated'],
                            'sub_total' => $subTotal,
                            'discount' => round($discount),
                        ],
                        'eta_minutes' => $config['eta_minutes'],
                        'is_available' => true
                    ];
                }

                return response()->json([
                    'status' => 'success',
                    'data' => [
                        'booking_type' => 'two_way',
                        'vehicle_options' => $options,
                        'currency' => 'PKR'
                    ]
                ]);
            }

            // Standard Logic: Multi-vehicle options
            $distance = (float)($request->distance ?? 0);
            $totalStops = $request->has('stops') ? count($request->stops) : 0;
            
            $options = [];
            foreach ($vehicleConfigs as $config) {
                $fareDetails = $this->calculateDetailedFare($config, $request->all());
                
                $options[] = [
                    'type' => $config['type'],
                    'name' => $config['name'],
                    'capacity' => $config['capacity'],
                    'fare' => $fareDetails,
                    'eta_minutes' => $config['eta_minutes'],
                    'is_available' => true
                ];
            }

            return response()->json([
                'status' => 'success',
                'data' => [
                    'distance_km' => $distance,
                    'duration_minutes' => (int)($request->time ?? 0),
                    'total_stops' => $totalStops,
                    'max_stops_allowed' => 4,
                    'vehicle_options' => $options
                ]
            ]);

        } catch (Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Fare estimation failed: ' . $e->getMessage()
            ], 500);
        }
    }

    private function calculateDetailedFare($config, $data)
    {
        $distance = (float)($data['distance'] ?? 0);
        $stopsData = $data['stops'] ?? [];
        $totalStops = (is_array($stopsData) || $stopsData instanceof \Countable) ? count($stopsData) : 0;
        
        $baseFare = $config['base_fare'];
        $distanceFare = $distance * $config['per_km'];
        $stopCharges = $totalStops * 20; // 20 PKR per stop
        
        $estimated = $baseFare + $distanceFare + $stopCharges;

        return [
            'estimated' => round($estimated),
            'base_fare' => $baseFare,
            'distance_fare' => round($distanceFare, 2),
            'stop_charges' => $stopCharges,
            'surge_multiplier' => 1
        ];
    }

    /**
     * POST /api/v1/rider/standard-book-ride
     * Book a new ride with optional multiple stops
     */
    public function standardBookRide(Request $request): JsonResponse
    {
        try {
            $validator = Validator::make($request->all(), [
                'pickup.place_name' => 'required_without:pickup.address|string',
                'pickup.latitude' => 'required|numeric',
                'pickup.longitude' => 'required|numeric',
                'destination.place_name' => 'required_without:destination.address|string',
                'destination.latitude' => 'required|numeric',
                'destination.longitude' => 'required|numeric',
                'ride_type' => 'required|string|in:bike,auto,economy,business,car_pool',
                'payment_method' => 'required|string|in:cash,wallet,card',
                'distance' => 'nullable|numeric',
                'duration' => 'nullable|numeric',
                'time' => 'nullable|numeric',
                'estimate_fare' => 'nullable|numeric',
                'stops' => 'nullable|array|max:4',
                'stops.*.place_name' => 'required_without:stops.*.address|string',
                'stops.*.latitude' => 'required|numeric',
                'stops.*.longitude' => 'required|numeric',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Validation failed',
                    'errors' => $validator->errors()
                ], 422);
            }

            $rider = auth()->user();

            /* 
            // Commenting out the active ride check to allow multiple bookings or re-booking after cancellation
            $activeRide = Ride::where('rider_id', $rider->id)
                ->whereIn('status', ['searching', 'accepted', 'ongoing', 'driver_arrived'])
                ->first();

            if ($activeRide) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'You already have an active ride request.'
                ], 403);
            }
            */

            return DB::transaction(function () use ($request, $rider) {
                // Determine duration and fare
                $duration = $request->duration ?? $request->time ?? 0;
                $fare = $request->estimate_fare ?? $this->calculateEstimatedFare($request->all());

                $ride = Ride::create([
                    'rider_id' => $rider->id,
                    'pickup_place_name' => $request->pickup['place_name'] ?? $request->pickup['address'],
                    'pickup_lat' => $request->pickup['latitude'],
                    'pickup_lng' => $request->pickup['longitude'],
                    'destination_place_name' => $request->destination['place_name'] ?? $request->destination['address'],
                    'destination_lat' => $request->destination['latitude'],
                    'destination_lng' => $request->destination['longitude'],
                    'ride_type' => $request->ride_type,
                    'payment_method' => $request->payment_method,
                    'status' => 'searching',
                    'estimated_fare' => $fare,
                    'distance_km' => $request->distance,
                    'duration_minutes' => $duration,
                ]);

                // Handle Multiple Stops
                if ($request->has('stops') && !empty($request->stops)) {
                    foreach ($request->stops as $index => $stop) {
                        RideStop::create([
                            'ride_id' => $ride->id,
                            'place_name' => $stop['place_name'] ?? $stop['address'],
                            'latitude' => $stop['latitude'],
                            'longitude' => $stop['longitude'],
                            'stop_order' => $index + 1,
                        ]);
                    }
                }

                return response()->json([
                    'status' => 'success',
                    'message' => 'Ride booked successfully. Searching for nearby drivers.',
                    'data' => ['ride_id' => $ride->id, 'ride' => $ride->load('stops')]
                ]);
            });
        } catch (Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to book ride: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * POST /api/v1/rider/two-way-ride
     * Book a round-trip ride
     */
    public function bookTwoWayRide(Request $request): JsonResponse
    {
        try {
            $validator = Validator::make($request->all(), [
                'go_trip.pickup.address' => 'required|string',
                'go_trip.pickup.latitude' => 'required|numeric',
                'go_trip.pickup.longitude' => 'required|numeric',
                'go_trip.destination.address' => 'required|string',
                'go_trip.destination.latitude' => 'required|numeric',
                'go_trip.destination.longitude' => 'required|numeric',
                'go_trip.date' => 'required|date|after_or_equal:today',
                'go_trip.time' => 'required|string',
                
                'return_trip.pickup.address' => 'required|string',
                'return_trip.pickup.latitude' => 'required|numeric',
                'return_trip.pickup.longitude' => 'required|numeric',
                'return_trip.destination.address' => 'required|string',
                'return_trip.destination.latitude' => 'required|numeric',
                'return_trip.destination.longitude' => 'required|numeric',
                'return_trip.date' => 'required|date|after_or_equal:go_trip.date',
                'return_trip.time' => 'required|string',
                
                'ride_type' => 'required|string|in:bike,auto,economy,business',
                'payment_method' => 'required|string|in:cash,wallet,card',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Validation failed',
                    'errors' => $validator->errors()
                ], 422);
            }

            $rider = auth()->user();

            // Check if rider already has an active ride
            $activeRide = Ride::where('rider_id', $rider->id)
                ->whereIn('status', ['searching', 'accepted', 'ongoing'])
                ->first();

            if ($activeRide) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'You already have an active ride request.'
                ], 403);
            }

            return DB::transaction(function () use ($request, $rider) {
                // 1. Create Forward Ride
                $goScheduledAt = date('Y-m-d H:i:s', strtotime($request->go_trip['date'] . ' ' . $request->go_trip['time']));
                
                $goRide = Ride::create([
                    'rider_id' => $rider->id,
                    'pickup_place_name' => $request->go_trip['pickup']['address'],
                    'pickup_lat' => $request->go_trip['pickup']['latitude'],
                    'pickup_lng' => $request->go_trip['pickup']['longitude'],
                    'destination_place_name' => $request->go_trip['destination']['address'],
                    'destination_lat' => $request->go_trip['destination']['latitude'],
                    'destination_lng' => $request->go_trip['destination']['longitude'],
                    'ride_type' => $request->ride_type,
                    'payment_method' => $request->payment_method,
                    'booking_type' => 'two_way',
                    'scheduled_at' => $goScheduledAt,
                    'status' => 'scheduled',
                    'estimated_fare' => $this->calculateEstimatedFare($request->go_trip),
                    'is_return' => false,
                ]);

                // 2. Create Return Ride
                $returnScheduledAt = date('Y-m-d H:i:s', strtotime($request->return_trip['date'] . ' ' . $request->return_trip['time']));
                
                $returnRide = Ride::create([
                    'rider_id' => $rider->id,
                    'pickup_place_name' => $request->return_trip['pickup']['address'],
                    'pickup_lat' => $request->return_trip['pickup']['latitude'],
                    'pickup_lng' => $request->return_trip['pickup']['longitude'],
                    'destination_place_name' => $request->return_trip['destination']['address'],
                    'destination_lat' => $request->return_trip['destination']['latitude'],
                    'destination_lng' => $request->return_trip['destination']['longitude'],
                    'ride_type' => $request->ride_type,
                    'payment_method' => $request->payment_method,
                    'booking_type' => 'two_way',
                    'scheduled_at' => $returnScheduledAt,
                    'status' => 'scheduled',
                    'estimated_fare' => $this->calculateEstimatedFare($request->return_trip),
                    'is_return' => true,
                    'linked_ride_id' => $goRide->id,
                ]);

                // 3. Link Forward to Return
                $goRide->update(['linked_ride_id' => $returnRide->id]);

                return response()->json([
                    'status' => 'success',
                    'message' => 'Two-way ride booked successfully.',
                    'data' => [
                        'go_ride_id' => $goRide->id,
                        'return_ride_id' => $returnRide->id,
                        'go_ride' => $goRide,
                        'return_ride' => $returnRide
                    ]
                ]);
            });
        } catch (Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to book two-way ride: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * GET /api/v1/rider/rides
     * Get all rides for the authenticated rider
     */
    public function getRides(Request $request): JsonResponse
    {
        try {
            $rider = auth()->user();
            $rides = Ride::where('rider_id', $rider->id)
                ->with(['driver', 'stops'])
                ->latest()
                ->get();

            return response()->json([
                'status' => 'success',
                'data' => $rides
            ]);
        } catch (Exception $e) {
            return response()->json(['status' => 'error', 'message' => 'Failed to fetch rides.'], 500);
        }
    }

    /**
     * GET /api/v1/rider/rides/active
     * Main polling endpoint for rider to check ride status and driver details
     */
    public function getActiveRide(Request $request): JsonResponse
    {
        try {
            $rider = auth()->user();

            $ride = Ride::with(['driver', 'stops'])
                ->where('rider_id', $rider->id)
                ->whereIn('status', ['searching', 'accepted', 'ongoing', 'driver_arrived'])
                ->first();

            return response()->json([
                'status' => 'success',
                'data' => [
                    'has_active_ride' => $ride !== null,
                    'ride' => $ride,
                ],
            ]);
        } catch (Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Error checking active ride status.'
            ], 500);
        }
    }

    /**
     * GET /api/v1/rider/rides/history
     * Get completed/cancelled rides history
     */
    public function getRideHistory(Request $request): JsonResponse
    {
        try {
            $rider = auth()->user();
            $rides = Ride::where('rider_id', $rider->id)
                ->whereIn('status', ['completed', 'cancelled'])
                ->with(['driver', 'stops'])
                ->latest()
                ->get();

            return response()->json([
                'status' => 'success',
                'data' => $rides
            ]);
        } catch (Exception $e) {
            return response()->json(['status' => 'error', 'message' => 'Failed to fetch history.'], 500);
        }
    }

    /**
     * GET /api/v1/rider/rides/{rideId}
     * Get specific ride details
     */
    public function getRideDetails($rideId): JsonResponse
    {
        try {
            $rider = auth()->user();
            $ride = Ride::with(['driver', 'stops'])
                ->where('id', $rideId)
                ->where('rider_id', $rider->id)
                ->first();

            if (!$ride) {
                return response()->json(['status' => 'error', 'message' => 'Ride not found.'], 404);
            }

            return response()->json([
                'status' => 'success',
                'data' => $ride
            ]);
        } catch (Exception $e) {
            return response()->json(['status' => 'error', 'message' => 'Error fetching ride details.'], 500);
        }
    }

    /**
     * PATCH /api/v1/rider/rides/{rideId}/cancel
     * Rider cancels the ride
     */
    public function cancelRide($rideId): JsonResponse
    {
        try {
            $rider = auth()->user();

            $ride = Ride::where('id', $rideId)
                ->where('rider_id', $rider->id)
                ->whereIn('status', ['searching', 'accepted', 'driver_arrived'])
                ->first();

            if (!$ride) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Ride not found or cannot be cancelled at this stage.',
                ], 404);
            }

            return DB::transaction(function () use ($ride) {
                $ride->update([
                    'status' => 'cancelled',
                    'cancelled_at' => now(),
                    'cancelled_by' => 'rider'
                ]);

                // If a driver was assigned, free them up
                if ($ride->driver_id) {
                    $ride->driver()->update([
                        'status' => 'online',
                        'is_available' => true,
                    ]);
                }

                return response()->json([
                    'status' => 'success',
                    'message' => 'Ride cancelled successfully.',
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

    /**
     * PATCH /api/v1/rider/rides/{rideId}/rate
     * Rider rates the driver
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

            $rider = auth()->user();
            $ride = Ride::where('id', $rideId)
                ->where('rider_id', $rider->id)
                ->where('status', 'completed')
                ->first();

            if (!$ride) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Completed ride not found to rate.',
                ], 404);
            }

            $ride->update([
                'driver_rating' => $request->rating,
                'driver_review' => $request->review,
            ]);

            return response()->json([
                'status' => 'success',
                'message' => 'Driver rated successfully.',
            ]);
        } catch (Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to submit rating.'
            ], 500);
        }
    }

    private function calculateEstimatedFare($data)
    {
        $rideType = $data['ride_type'] ?? 'bike';
        $distance = (float)($data['distance'] ?? 0);
        
        $stopsData = $data['stops'] ?? [];
        $totalStops = (is_array($stopsData) || $stopsData instanceof \Countable) ? count($stopsData) : 0;

        // Rates Configuration
        $rates = [
            'bike'     => ['base' => 30,  'per_km' => 8],
            'auto'     => ['base' => 60,  'per_km' => 20],
            'economy'  => ['base' => 80,  'per_km' => 25],
            'business' => ['base' => 150, 'per_km' => 40],
            'car_pool' => ['base' => 50,  'per_km' => 15],
        ];

        $config = $rates[$rideType] ?? $rates['bike'];
        
        $baseFare = $config['base'];
        $distanceFare = $distance * $config['per_km'];
        $stopCharges = $totalStops * 20;

        $total = $baseFare + $distanceFare + $stopCharges;

        // If no distance is provided, use a reasonable default
        if ($distance <= 0) {
            return 450.00;
        }

        return round($total);
    }
}
