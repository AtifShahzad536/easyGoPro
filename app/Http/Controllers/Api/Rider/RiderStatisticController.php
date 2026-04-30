<?php

namespace App\Http\Controllers\Api\Rider;

use App\Http\Controllers\Controller;
use App\Models\Rider;
use App\Models\Ride;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class RiderStatisticController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth:sanctum');
    }

    /**
     * Get authenticated rider's statistics
     */
    public function getMyStatistics(Request $request): JsonResponse
    {
        $rider = $request->user();

        if (!$rider) {
            return response()->json([
                'status' => 'error',
                'message' => 'Rider not authenticated'
            ], 401);
        }

        $statistics = $rider->statistics;
        
        // Calculate real-time average rating from completed rides
        $averageRating = Ride::where('rider_id', $rider->id)
            ->whereNotNull('rider_rating')
            ->avg('rider_rating') ?? 0;

        if (!$statistics) {
            return response()->json([
                'status' => 'error',
                'message' => 'Statistics not found for this rider'
            ], 404);
        }

        // Calculate additional metrics
        $completionRate = $statistics->total_trips > 0
            ? round(($statistics->completed_trips / $statistics->total_trips) * 100, 2)
            : 0;

        return response()->json([
            'status' => 'success',
            'data' => [
                'spending' => [
                    'total_spent' => $statistics->total_spent,
                    'wallet_balance' => $statistics->wallet_balance,
                    'total_refunded' => $statistics->total_refunded,
                ],
                'trips' => [
                    'total_trips' => $statistics->total_trips,
                    'completed_trips' => $statistics->completed_trips,
                    'cancelled_trips' => $statistics->cancelled_trips,
                    'completion_rate' => $completionRate,
                ],
                'rating' => [
                    'average_rating' => round($averageRating, 2),
                ],
                'last_ride_at' => $statistics->last_ride_at,
                'updated_at' => $statistics->updated_at,
            ]
        ]);
    }

    /**
     * Get rider's dashboard summary
     */
    public function getDashboardSummary(Request $request): JsonResponse
    {
        $rider = $request->user();

        if (!$rider) {
            return response()->json([
                'status' => 'error',
                'message' => 'Rider not authenticated'
            ], 401);
        }

        $statistics = $rider->statistics;

        if (!$statistics) {
            return response()->json([
                'status' => 'error',
                'message' => 'Statistics not found'
            ], 404);
        }

        return response()->json([
            'status' => 'success',
            'data' => [
                'quick_stats' => [
                    'total_trips' => $statistics->total_trips,
                    'wallet_balance' => $statistics->wallet_balance,
                    'average_rating' => round(Ride::where('rider_id', $rider->id)->whereNotNull('rider_rating')->avg('rider_rating') ?? 0, 2),
                ],
                'rider_info' => [
                    'full_name' => $rider->full_name,
                    'mobile_number' => $rider->mobile_number,
                    'is_active' => $rider->is_active,
                ]
            ]
        ]);
    }
}
