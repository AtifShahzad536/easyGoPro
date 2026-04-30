<?php

namespace App\Http\Controllers\Api\Driver;

use App\Http\Controllers\Controller;
use App\Models\Driver;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class DriverStatisticController extends Controller
{
    /**
     * Get authenticated driver's statistics
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function getMyStatistics(Request $request): JsonResponse
    {
        $driver = $request->user();

        if (!$driver) {
            return response()->json([
                'success' => false,
                'message' => 'Driver not authenticated'
            ], 401);
        }

        $statistics = $driver->statistics;
        
        // Calculate real-time average rating from completed rides
        $averageRating = \App\Models\Ride::where('driver_id', $driver->id)
            ->whereNotNull('driver_rating')
            ->avg('driver_rating') ?? 0;

        if (!$statistics) {
            return response()->json([
                'success' => false,
                'message' => 'Statistics not found for this driver'
            ], 404);
        }

        // Calculate additional metrics
        $completionRate = $statistics->total_trips > 0
            ? round(($statistics->completed_trips / $statistics->total_trips) * 100, 2)
            : 0;

        $cancellationRate = $statistics->total_trips > 0
            ? round(($statistics->cancelled_trips / $statistics->total_trips) * 100, 2)
            : 0;

        $availableBalance = $statistics->wallet_balance - $statistics->total_withdrawn;

        return response()->json([
            'success' => true,
            'data' => [
                'earnings' => [
                    'total_earnings' => $statistics->total_earnings,
                    'wallet_balance' => $statistics->wallet_balance,
                    'total_withdrawn' => $statistics->total_withdrawn,
                    'available_balance' => $availableBalance,
                ],
                'trips' => [
                    'total_trips' => $statistics->total_trips,
                    'completed_trips' => $statistics->completed_trips,
                    'cancelled_trips' => $statistics->cancelled_trips,
                    'completion_rate' => $completionRate,
                    'cancellation_rate' => $cancellationRate,
                ],
                'rating' => [
                    'average_rating' => round($averageRating, 2),
                ],
                'activity' => [
                    'total_online_minutes' => $statistics->total_online_minutes,
                    'total_online_hours' => round($statistics->total_online_minutes / 60, 2),
                    'last_trip_at' => $statistics->last_trip_at,
                ],
                'updated_at' => $statistics->updated_at,
            ]
        ]);
    }

    /**
     * Get driver's dashboard summary
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function getDashboardSummary(Request $request): JsonResponse
    {
        $driver = $request->user();

        if (!$driver) {
            return response()->json([
                'success' => false,
                'message' => 'Driver not authenticated'
            ], 401);
        }

        $statistics = $driver->statistics;

        if (!$statistics) {
            return response()->json([
                'success' => false,
                'message' => 'Statistics not found'
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data' => [
                'quick_stats' => [
                    'today_earnings' => 0, // Can be updated with daily tracking
                    'today_trips' => 0,
                    'wallet_balance' => $statistics->wallet_balance,
                    'average_rating' => round(\App\Models\Ride::where('driver_id', $driver->id)->whereNotNull('driver_rating')->avg('driver_rating') ?? 0, 2),
                ],
                'overall_performance' => [
                    'total_earnings' => $statistics->total_earnings,
                    'total_trips' => $statistics->total_trips,
                    'completed_trips' => $statistics->completed_trips,
                    'completion_rate' => $statistics->total_trips > 0
                        ? round(($statistics->completed_trips / $statistics->total_trips) * 100, 2)
                        : 0,
                ],
                'driver_info' => [
                    'full_name' => $driver->full_name,
                    'status' => $driver->status,
                    'kyc_status' => $driver->kyc_status,
                    'is_available' => $driver->is_available,
                ]
            ]
        ]);
    }

    /**
     * Update driver's statistics
     * Note: This is typically called internally by the system
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function updateStatistics(Request $request): JsonResponse
    {
        $driver = $request->user();

        if (!$driver) {
            return response()->json([
                'success' => false,
                'message' => 'Driver not authenticated'
            ], 401);
        }

        $validator = Validator::make($request->all(), [
            'wallet_balance' => 'nullable|numeric|min:0',
            'total_earnings' => 'nullable|numeric|min:0',
            'total_withdrawn' => 'nullable|numeric|min:0',
            'average_rating' => 'nullable|numeric|min:0|max:5',
            'total_trips' => 'nullable|integer|min:0',
            'completed_trips' => 'nullable|integer|min:0',
            'cancelled_trips' => 'nullable|integer|min:0',
            'cancellation_count' => 'nullable|integer|min:0',
            'total_online_minutes' => 'nullable|integer|min:0',
            'last_trip_at' => 'nullable|date',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        $statistics = $driver->statistics;

        if (!$statistics) {
            return response()->json([
                'success' => false,
                'message' => 'Statistics not found'
            ], 404);
        }

        $statistics->update($request->only([
            'wallet_balance',
            'total_earnings',
            'total_withdrawn',
            'average_rating',
            'total_trips',
            'completed_trips',
            'cancelled_trips',
            'cancellation_count',
            'total_online_minutes',
            'last_trip_at',
        ]));

        return response()->json([
            'success' => true,
            'message' => 'Statistics updated successfully',
            'data' => $statistics->fresh()
        ]);
    }

    /**
     * Get earnings history (placeholder for future implementation)
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function getEarningsHistory(Request $request): JsonResponse
    {
        $driver = $request->user();

        if (!$driver) {
            return response()->json([
                'success' => false,
                'message' => 'Driver not authenticated'
            ], 401);
        }

        // This is a placeholder - in production, you'd fetch from a transactions table
        return response()->json([
            'success' => true,
            'data' => [
                'total_earnings' => $driver->statistics?->total_earnings ?? 0,
                'message' => 'Earnings history endpoint - implement with transactions table'
            ]
        ]);
    }

    /**
     * Get trip statistics breakdown
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function getTripStatistics(Request $request): JsonResponse
    {
        $driver = $request->user();

        if (!$driver) {
            return response()->json([
                'success' => false,
                'message' => 'Driver not authenticated'
            ], 401);
        }

        $statistics = $driver->statistics;

        if (!$statistics) {
            return response()->json([
                'success' => false,
                'message' => 'Statistics not found'
            ], 404);
        }

        $total = $statistics->total_trips;

        return response()->json([
            'success' => true,
            'data' => [
                'total' => $total,
                'completed' => [
                    'count' => $statistics->completed_trips,
                    'percentage' => $total > 0 ? round(($statistics->completed_trips / $total) * 100, 2) : 0,
                ],
                'cancelled' => [
                    'count' => $statistics->cancelled_trips,
                    'percentage' => $total > 0 ? round(($statistics->cancelled_trips / $total) * 100, 2) : 0,
                ],
                'cancellation_by_driver' => $statistics->cancellation_count,
                'completion_rate' => $total > 0 ? round(($statistics->completed_trips / $total) * 100, 2) : 0,
            ]
        ]);
    }
}
