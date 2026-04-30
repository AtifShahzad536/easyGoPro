<?php

namespace App\Http\Controllers\Api\Rider;

use App\Http\Controllers\Controller;
use App\Models\Rider;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;

class RiderProfileController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth:sanctum');
    }

    /**
     * Get rider profile (without display_name)
     */
    public function getProfile(Request $request): JsonResponse
    {
        $rider = $request->user();

        // Calculate real-time average rating from completed rides
        $averageRating = \App\Models\Ride::where('rider_id', $rider->id)
            ->whereNotNull('rider_rating')
            ->avg('rider_rating') ?? 0;

        // Hide sensitive fields including display_name
        $profile = [
            'id' => $rider->id,
            'full_name' => $rider->full_name,
            'mobile_number' => $rider->mobile_number,
            'email' => $rider->email,
            'profile_photo' => $rider->profile_photo ? asset('storage/' . $rider->profile_photo) : null,
            'gender' => $rider->gender,
            'date_of_birth' => $rider->date_of_birth,
            'rating' => round($averageRating, 2),
            'is_active' => $rider->is_active,
            'created_at' => $rider->created_at?->toDateTimeString(),
        ];

        return response()->json([
            'status' => 'success',
            'data' => $profile,
        ]);
    }

    /**
     * Update rider profile
     */
    public function updateProfile(Request $request): JsonResponse
    {
        $rider = $request->user();
        $riderId = $rider->id;

        // Handle form-data on PUT requests (PHP doesn't parse it automatically)
        if ($request->isMethod('put') || $request->isMethod('patch')) {
            $contentType = $request->header('Content-Type', '');
            if (strpos($contentType, 'multipart/form-data') !== false) {
                // Parse form-data from php://input
                $input = file_get_contents('php://input');
                if (!empty($input)) {
                    $boundary = explode('boundary=', $contentType)[1] ?? '';
                    if ($boundary) {
                        $parts = explode('--' . $boundary, $input);
                        foreach ($parts as $part) {
                            if (strpos($part, 'Content-Disposition: form-data; name="') !== false) {
                                preg_match('/name="([^"]+)"/', $part, $nameMatch);
                                if ($nameMatch) {
                                    $fieldName = $nameMatch[1];
                                    $value = substr($part, strpos($part, "\r\n\r\n") + 4, -2);
                                    $request->merge([$fieldName => $value]);
                                }
                            }
                        }
                    }
                }
            }
        }

        $validator = Validator::make($request->all(), [
            'full_name' => 'sometimes|string|max:255',
            'email' => 'sometimes|email|unique:riders,email,' . $riderId,
            'gender' => 'sometimes|in:male,female,other',
            'date_of_birth' => 'sometimes|date|before:today',
            'profile_photo' => 'sometimes|image|mimes:jpg,jpeg,png|max:2048',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
            ], 422);
        }

        // Re-fetch fresh instance from database
        $rider = Rider::find($riderId);

        // Handle profile photo upload
        if ($request->hasFile('profile_photo')) {
            // Delete old photo
            if ($rider->profile_photo && Storage::disk('public')->exists($rider->profile_photo)) {
                Storage::disk('public')->delete($rider->profile_photo);
            }

            // Store new photo
            $path = $request->file('profile_photo')->store('profile_photos/riders', 'public');
            $rider->profile_photo = $path;
        }

        // Update other fields
        if ($request->filled('full_name')) {
            $rider->full_name = $request->full_name;
        }
        if ($request->filled('email')) {
            $rider->email = $request->email;
        }
        if ($request->filled('gender')) {
            $rider->gender = $request->gender;
        }
        if ($request->filled('date_of_birth')) {
            $rider->date_of_birth = $request->date_of_birth;
        }

        $rider->save();
        $rider->refresh();

        // Return updated profile (without display_name)
        return response()->json([
            'status' => 'success',
            'message' => 'Profile updated successfully',
            'data' => [
                'id' => $rider->id,
                'full_name' => $rider->full_name,
                'mobile_number' => $rider->mobile_number,
                'email' => $rider->email,
                'profile_photo' => $rider->profile_photo ? asset('storage/' . $rider->profile_photo) : null,
                'gender' => $rider->gender,
                'date_of_birth' => $rider->date_of_birth,
                'rating' => round(\App\Models\Ride::where('rider_id', $rider->id)->whereNotNull('rider_rating')->avg('rider_rating') ?? 0, 2),
                'updated_at' => $rider->updated_at->toDateTimeString(),
            ],
        ]);
    }
}
