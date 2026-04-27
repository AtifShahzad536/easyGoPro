<?php

namespace App\Http\Controllers\Api\Driver;

use App\Http\Controllers\Controller;
use App\Models\Driver;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Throwable;

class DriverStatusController extends Controller
{
    /**
     * Update driver status (online/offline/busy etc.)
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function updateStatus(Request $request): JsonResponse
    {
        try {
            $driver = $request->user();

            if (!$driver) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Unauthorized. Please login first.',
                ], 401);
            }

            $validator = Validator::make($request->all(), [
                'status' => 'required|string|in:online,offline,busy,on_ride,inactive',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Validation failed',
                    'errors' => $validator->errors(),
                ], 422);
            }

            // Update status
            $driver->status = $request->status;

            // Also update is_available based on status
            $driver->is_available = ($request->status === 'online');

            $driver->save();

            return response()->json([
                'status' => 'success',
                'message' => 'Driver status updated successfully',
                'data' => [
                    'driver_id' => $driver->id,
                    'status' => $driver->status,
                    'is_available' => $driver->is_available,
                    'updated_at' => $driver->updated_at->toDateTimeString(),
                ],
            ], 200);

        } catch (Throwable $e) {
            Log::error('Driver status update error: ' . $e->getMessage(), [
                'driver_id' => $request->user()?->id,
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json([
                'status' => 'error',
                'message' => 'An error occurred while updating status. Please try again.',
            ], 500);
        }
    }

    /**
     * Get current driver status
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function getStatus(Request $request): JsonResponse
    {
        try {
            $driver = $request->user();

            if (!$driver) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Unauthorized. Please login first.',
                ], 401);
            }

            return response()->json([
                'status' => 'success',
                'data' => [
                    'driver_id' => $driver->id,
                    'status' => $driver->status ?? 'offline',
                    'is_available' => $driver->is_available ?? false,
                    'last_updated' => $driver->updated_at?->toDateTimeString(),
                ],
            ], 200);

        } catch (Throwable $e) {
            Log::error('Driver status get error: ' . $e->getMessage());

            return response()->json([
                'status' => 'error',
                'message' => 'An error occurred while fetching status.',
            ], 500);
        }
    }
}
