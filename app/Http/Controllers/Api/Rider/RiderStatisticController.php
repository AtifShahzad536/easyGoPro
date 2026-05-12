<?php

namespace App\Http\Controllers\Api\Rider;

use App\Http\Controllers\Controller;
use App\Traits\RideMetricsTrait;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Exception;

class RiderStatisticController extends Controller
{
    use RideMetricsTrait;

    public function __construct()
    {
        $this->middleware('auth:sanctum');
    }

    /**
     * Get authenticated rider's statistics
     */
    public function getMyStatistics(Request $request): JsonResponse
    {
        try {
            $rider = $request->user();

            if (!$rider) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Unauthorized access.'
                ], 401);
            }

            $rideStats = $this->getRealTimeMetrics($rider->id, 'rider');
            $statistics = $rider->statistics;

            $totalTrips = (int)($rideStats->total_trips ?? 0);
            $completedTrips = (int)($rideStats->completed_trips ?? 0);

            return response()->json([
                'status' => 'success',
                'data' => [
                    'spending' => [
                        'total_spent' => (float)($rideStats->total_amount ?? 0),
                        'wallet_balance' => (float)($statistics->wallet_balance ?? 0),
                        'total_refunded' => (float)($statistics->total_refunded ?? 0),
                    ],
                    'trips' => [
                        'total_trips' => $totalTrips,
                        'completed_trips' => $completedTrips,
                        'cancelled_trips' => (int)($rideStats->cancelled_trips ?? 0),
                        'completion_rate' => $totalTrips > 0 ? round(($completedTrips / $totalTrips) * 100, 2) : 0,
                    ],
                    'rating' => [
                        'average_rating' => round((float)($rideStats->average_rating ?? 0), 2),
                    ],
                    'last_ride_at' => $statistics->last_ride_at ?? null,
                ]
            ]);
        } catch (Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Unable to fetch statistics at this time.'
            ], 500);
        }
    }

    /**
     * Get rider's dashboard summary
     */
    public function getDashboardSummary(Request $request): JsonResponse
    {
        try {
            $rider = $request->user();

            if (!$rider) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Rider not found.'
                ], 401);
            }

            $rideStats = $this->getRealTimeMetrics($rider->id, 'rider');

            return response()->json([
                'status' => 'success',
                'data' => [
                    'quick_stats' => [
                        'total_trips' => (int)($rideStats->total_trips ?? 0),
                        'wallet_balance' => (float)($rider->statistics->wallet_balance ?? 0),
                        'average_rating' => round((float)($rideStats->average_rating ?? 0), 2),
                    ],
                    'rider_info' => [
                        'full_name' => $rider->full_name,
                        'mobile_number' => $rider->mobile_number,
                        'is_active' => (bool)$rider->is_active,
                    ]
                ]
            ]);
        } catch (Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Error loading dashboard summary.'
            ], 500);
        }
    }
}
