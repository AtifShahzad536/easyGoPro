<?php

namespace App\Http\Controllers\Api\Driver;

use App\Http\Controllers\Controller;
use App\Traits\RideMetricsTrait;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Exception;

class DriverStatisticController extends Controller
{
    use RideMetricsTrait;

    /**
     * Get authenticated driver's statistics
     */
    public function getMyStatistics(Request $request): JsonResponse
    {
        try {
            $driver = $request->user();

            if (!$driver) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Driver authentication failed. Please login again.'
                ], 401);
            }

            // Fetch real-time metrics using shared trait
            $rideStats = $this->getRealTimeMetrics($driver->id, 'driver');
            $statistics = $driver->statistics;

            $totalTrips = (int)($rideStats->total_trips ?? 0);
            $completedTrips = (int)($rideStats->completed_trips ?? 0);
            $cancelledTrips = (int)($rideStats->cancelled_trips ?? 0);
            
            $completionRate = $totalTrips > 0 ? round(($completedTrips / $totalTrips) * 100, 2) : 0;
            $cancellationRate = $totalTrips > 0 ? round(($cancelledTrips / $totalTrips) * 100, 2) : 0;

            $walletBalance = (float)($statistics->wallet_balance ?? 0);
            $totalWithdrawn = (float)($statistics->total_withdrawn ?? 0);

            return response()->json([
                'status' => 'success',
                'data' => [
                    'earnings' => [
                        'total_earnings' => (float)($rideStats->total_amount ?? 0),
                        'wallet_balance' => $walletBalance,
                        'total_withdrawn' => $totalWithdrawn,
                        'available_balance' => $walletBalance - $totalWithdrawn,
                    ],
                    'trips' => [
                        'total_trips' => $totalTrips,
                        'completed_trips' => $completedTrips,
                        'cancelled_trips' => $cancelledTrips,
                        'completion_rate' => $completionRate,
                        'cancellation_rate' => $cancellationRate,
                    ],
                    'rating' => [
                        'average_rating' => round((float)($rideStats->average_rating ?? 0), 2),
                    ],
                    'activity' => [
                        'total_online_minutes' => (int)($statistics->total_online_minutes ?? 0),
                        'total_online_hours' => round(($statistics->total_online_minutes ?? 0) / 60, 2),
                        'last_trip_at' => $statistics->last_trip_at ?? null,
                    ]
                ]
            ]);
        } catch (Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Could not retrieve statistics. Please try again later.',
                'debug' => config('app.debug') ? $e->getMessage() : null
            ], 500);
        }
    }

    /**
     * Get driver's dashboard summary
     */
    public function getDashboardSummary(Request $request): JsonResponse
    {
        try {
            $driver = $request->user();

            if (!$driver) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Session expired. Please log in.'
                ], 401);
            }

            $rideStats = $this->getRealTimeMetrics($driver->id, 'driver');
            $statistics = $driver->statistics;

            return response()->json([
                'status' => 'success',
                'data' => [
                    'quick_stats' => [
                        'total_trips' => (int)($rideStats->total_trips ?? 0),
                        'today_earnings' => 0, // Logic for today can be added if needed
                        'wallet_balance' => (float)($statistics->wallet_balance ?? 0),
                        'average_rating' => round((float)($rideStats->average_rating ?? 0), 2),
                    ],
                    'driver_info' => [
                        'full_name' => $driver->full_name,
                        'status' => $driver->status,
                        'is_available' => (bool)$driver->is_available,
                    ]
                ]
            ]);
        } catch (Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to load dashboard data.'
            ], 500);
        }
    }
}
