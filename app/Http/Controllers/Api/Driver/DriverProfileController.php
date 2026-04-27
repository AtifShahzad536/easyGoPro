<?php

namespace App\Http\Controllers\Api\Driver;

use App\Http\Controllers\Controller;
use App\Models\Driver;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;

class DriverProfileController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth:sanctum');
    }

    /**
     * Get driver profile
     */
    public function getProfile(Request $request): JsonResponse
    {
        $driver = $request->user();
        $vehicle = $driver->vehicle;

        return response()->json([
            'status' => 'success',
            'data' => [
                'id' => $driver->id,
                'full_name' => $driver->full_name,
                'mobile_number' => $driver->mobile_number,
                'email' => $driver->email,
                'profile_photo' => $driver->profile_photo ? asset('storage/' . $driver->profile_photo) : null,
                'cnic' => $driver->cnic,
                'cnic_name' => $driver->cnic_name,
                'date_of_birth' => $driver->date_of_birth,
                'gender' => $driver->gender,
                'status' => $driver->status,
                'kyc_status' => $driver->kyc_status,
                'is_available' => $driver->is_available,
                'created_at' => $driver->created_at?->toDateTimeString(),
            ],
            'vehicle' => $vehicle ? [
                'id' => $vehicle->id,
                'make' => $vehicle->make,
                'model' => $vehicle->model,
                'year' => $vehicle->year,
                'color' => $vehicle->color,
                'plate_number' => $vehicle->plate_number,
                'is_active' => $vehicle->is_active,
                'type' => $vehicle->type,
                'updated_at' => $vehicle->updated_at->toDateTimeString(),
            ] : null,
        ]);
    }

    /**
     * Update driver profile
     */
    public function updateProfile(Request $request): JsonResponse
    {
        $driver = $request->user();
        $driverId = $driver->id;

        // Handle form-data on PUT requests
        if ($request->isMethod('put') || $request->isMethod('patch')) {
            $contentType = $request->header('Content-Type', '');
            if (strpos($contentType, 'multipart/form-data') !== false) {
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
            'email' => 'sometimes|email|unique:drivers,email,' . $driverId,
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
        $driver = Driver::find($driverId);
        $vehicle = $driver->vehicle;

        // Handle profile photo upload
        if ($request->hasFile('profile_photo')) {
            if ($driver->profile_photo && Storage::disk('public')->exists($driver->profile_photo)) {
                Storage::disk('public')->delete($driver->profile_photo);
            }
            $path = $request->file('profile_photo')->store('profile_photos/drivers', 'public');
            $driver->profile_photo = $path;
        }

        // Update fields using filled() for proper form-data handling
        if ($request->filled('full_name')) {
            $driver->full_name = $request->full_name;
        }
        if ($request->filled('email')) {
            $driver->email = $request->email;
        }
        if ($request->filled('gender')) {
            $driver->gender = $request->gender;
        }
        if ($request->filled('date_of_birth')) {
            $driver->date_of_birth = $request->date_of_birth;
        }

        $driver->save();
        $driver->refresh();

        return response()->json([
            'status' => 'success',
            'message' => 'Profile updated successfully',
            'data' => [
                'id' => $driver->id,
                'full_name' => $driver->full_name,
                'mobile_number' => $driver->mobile_number,
                'email' => $driver->email,
                'profile_photo' => $driver->profile_photo ? asset('storage/' . $driver->profile_photo) : null,
                'gender' => $driver->gender,
                'date_of_birth' => $driver->date_of_birth,
                'updated_at' => $driver->updated_at->toDateTimeString(),
            ],
            'vehicle' => $vehicle ? [
                'id' => $vehicle->id,
                'make' => $vehicle->make,
                'type' => $vehicle->type,
                'model' => $vehicle->model,
                'year' => $vehicle->year,
                'color' => $vehicle->color,
                'plate_number' => $vehicle->plate_number,
                'is_active' => $vehicle->is_active,
                'updated_at' => $vehicle->updated_at->toDateTimeString(),
            ] : null,
        ]);
    }
}
