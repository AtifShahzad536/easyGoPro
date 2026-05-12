<?php

namespace App\Http\Controllers\Api\Rider;

use App\Http\Controllers\Controller;
use App\Models\Rider;
use App\Traits\RideMetricsTrait;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;
use Exception;

class RiderProfileController extends Controller
{
    use RideMetricsTrait;

    /**
     * Get authenticated rider profile
     */
    public function getProfile(Request $request): JsonResponse
    {
        try {
            $rider = $request->user();

            if (!$rider) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Profile not found.'
                ], 404);
            }

            // Real-time rating from trait
            $rideStats = $this->getRealTimeMetrics($rider->id, 'rider');

            return response()->json([
                'status' => 'success',
                'data' => [
                    'id' => $rider->id,
                    'full_name' => $rider->full_name,
                    'mobile_number' => $rider->mobile_number,
                    'email' => $rider->email,
                    'profile_photo' => $rider->profile_photo ? url('storage/' . $rider->profile_photo) : null,
                    'gender' => $rider->gender,
                    'date_of_birth' => $rider->date_of_birth,
                    'rating' => round((float)($rideStats->average_rating ?? 0), 2),
                    'is_active' => $rider->status === 'active',
                    'created_at' => $rider->created_at?->toDateTimeString(),
                ]
            ]);
        } catch (Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Internal server error while fetching profile.',
                'error' => config('app.debug') ? $e->getMessage() : null
            ], 500);
        }
    }

    /**
     * Update rider profile
     */
    public function updateProfile(Request $request): JsonResponse
    {
        try {
            $rider = $request->user();

            $validator = Validator::make($request->all(), [
                'full_name' => 'sometimes|string|max:255',
                'email' => 'sometimes|email|unique:riders,email,' . $rider->id,
                'profile_photo' => 'sometimes|image|mimes:jpeg,png,jpg|max:2048',
                'gender' => 'sometimes|string|in:male,female,other',
                'date_of_birth' => 'sometimes|date',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Validation failed',
                    'errors' => $validator->errors()
                ], 422);
            }

            $data = $request->only(['full_name', 'email', 'gender', 'date_of_birth']);

            if ($request->hasFile('profile_photo')) {
                // Delete old photo
                if ($rider->profile_photo) {
                    Storage::disk('public')->delete($rider->profile_photo);
                }
                $path = $request->file('profile_photo')->store('profile_photos/riders', 'public');
                $data['profile_photo'] = $path;
            }

            $rider->update($data);

            return response()->json([
                'status' => 'success',
                'message' => 'Profile updated successfully',
                'data' => $rider
            ]);
        } catch (Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to update profile.',
                'error' => config('app.debug') ? $e->getMessage() : null
            ], 500);
        }
    }
}
