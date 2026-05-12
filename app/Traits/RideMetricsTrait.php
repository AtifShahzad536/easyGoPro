<?php

namespace App\Traits;

use App\Models\Ride;

trait RideMetricsTrait
{
    /**
     * Calculate real-time metrics for a user (Driver or Rider)
     */
    public function getRealTimeMetrics($userId, $role = 'driver')
    {
        $column = $role === 'driver' ? 'driver_id' : 'rider_id';
        $ratingColumn = $role === 'driver' ? 'driver_rating' : 'rider_rating';
        $fareColumn = 'final_fare';

        return Ride::where($column, $userId)
            ->selectRaw("
                COUNT(*) as total_trips,
                SUM(CASE WHEN status = 'completed' THEN 1 ELSE 0 END) as completed_trips,
                SUM(CASE WHEN status = 'cancelled' THEN 1 ELSE 0 END) as cancelled_trips,
                SUM(CASE WHEN status = 'completed' THEN {$fareColumn} ELSE 0 END) as total_amount,
                AVG({$ratingColumn}) as average_rating
            ")->first();
    }
}
