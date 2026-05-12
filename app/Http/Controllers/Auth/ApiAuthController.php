<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\Driver;
use App\Models\Rider;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Throwable;
use Exception;

class ApiAuthController extends Controller
{
    /**
     * Register a new Driver or Rider
     */
    public function register(Request $request)
    {
        try {
            $role = $request->input('role');

            if ($role === 'driver') {
                return $this->registerDriver($request);
            } elseif ($role === 'rider') {
                return $this->registerRider($request);
            }

            return response()->json([
                'status'  => 'error',
                'message' => 'Invalid role specified. Use "driver" or "rider".',
            ], 422);
        } catch (Exception $e) {
            Log::error('Registration Error: ' . $e->getMessage());
            return response()->json([
                'status'  => 'error',
                'message' => 'Something went wrong during registration. Please try again later.'
            ], 500);
        }
    }

    /**
     * Login for both roles
     */
    public function login(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'mobile_number' => 'required|string',
                'role'          => 'required|in:rider,driver',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'status'  => 'error',
                    'message' => 'Validation error',
                    'errors'  => $validator->errors(),
                ], 422);
            }

            $model = $request->role === 'driver' ? Driver::class : Rider::class;
            $user = $model::where('mobile_number', $request->mobile_number)->first();
            
            if (!$user) {
                return response()->json([
                    'status'  => 'error',
                    'message' => 'Account not found. Please register first.',
                ], 404);
            }

            // Security Checks
            if ($request->role === 'driver' && $user->status === 'suspended') {
                return response()->json(['status' => 'error', 'message' => 'Account suspended.'], 403);
            }
            if ($request->role === 'rider' && $user->status === 'banned') {
                return response()->json(['status' => 'error', 'message' => 'Account banned.'], 403);
            }

            return DB::transaction(function () use ($user, $request) {
                if ($request->role === 'driver') {
                    $user->is_available = true;
                }

                $user->tokens()->delete();
                $token = $user->createToken('auth_token')->plainTextToken;
                
                $user->session_token = $token;
                $user->remember_token = Str::random(60);
                $user->save();

                $userData = $user->toArray();
                if ($user->profile_photo) {
                    $userData['profile_photo_url'] = url('storage/' . $user->profile_photo);
                }

                return response()->json([
                    'status'       => 'success',
                    'message'      => 'Login successful',
                    'access_token' => $token,
                    'role'         => $request->role,
                    'user'         => $userData,
                ]);
            });
        } catch (Exception $e) {
            Log::error('Login Error: ' . $e->getMessage());
            return response()->json([
                'status'  => 'error',
                'message' => 'An internal error occurred during login.'
            ], 500);
        }
    }

    private function registerDriver(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'full_name'      => 'required|string|max:255',
            'mobile_number'  => 'required|string|unique:drivers,mobile_number',
            'cnic'           => 'required|string|unique:drivers,cnic',
            'cnic_name'      => 'required|string|max:255',
            'email'          => 'nullable|email|unique:drivers,email',
            'date_of_birth'  => 'required|date',
            'gender'         => 'required|in:male,female,other',
            'profile_photo'  => 'nullable|image|max:2048',
            'vehicle'        => 'required|array',
            'documents'      => 'required|array',
        ]);

        if ($validator->fails()) {
            return response()->json(['status' => 'error', 'errors' => $validator->errors()], 422);
        }

        try {
            return DB::transaction(function () use ($request) {
                $data = $request->except(['profile_photo', 'role', 'vehicle', 'documents']);
                $data['password'] = Hash::make(Str::random(16));
                $data['status'] = 'offline';
                $data['kyc_status'] = 'pending';

                if ($request->hasFile('profile_photo')) {
                    $data['profile_photo'] = $request->file('profile_photo')->store('profile_photos/drivers', 'public');
                }

                $driver = Driver::create($data);
                $driver->statistics()->create([]);

                // Vehicle Registration
                $vehicleData = $request->input('vehicle');
                $driver->vehicle()->create(array_merge($vehicleData, ['is_active' => false]));

                // Documents Upload
                foreach ($request->file('documents') as $type => $file) {
                    $path = $file->store("driver_documents/{$driver->id}", 'public');
                    $driver->documents()->create(['type' => $type, 'file_path' => $path, 'status' => 'pending']);
                }

                $token = $driver->createToken('auth_token')->plainTextToken;
                $driver->update(['session_token' => $token]);

                return response()->json([
                    'status'  => 'success',
                    'message' => 'Driver registered. Please wait for document verification.',
                    'access_token' => $token,
                    'user' => $driver->load(['vehicle', 'statistics'])
                ], 201);
            });
        } catch (Exception $e) {
            Log::error('Driver Reg Error: ' . $e->getMessage());
            return response()->json(['status' => 'error', 'message' => 'Registration failed.'], 500);
        }
    }

    private function registerRider(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'full_name'      => 'required|string|max:255',
            'display_name'   => 'required|string|max:255',
            'mobile_number'  => 'required|string|unique:riders,mobile_number',
            'email'          => 'nullable|email|unique:riders,email',
            'gender'         => 'required|in:male,female,other',
            'date_of_birth'  => 'required|date',
            'profile_photo'  => 'nullable|image|max:2048',
        ]);

        if ($validator->fails()) {
            return response()->json(['status' => 'error', 'errors' => $validator->errors()], 422);
        }

        try {
            return DB::transaction(function () use ($request) {
                $data = $request->except(['profile_photo', 'role']);
                $data['password'] = Hash::make(Str::random(16));

                if ($request->hasFile('profile_photo')) {
                    $data['profile_photo'] = $request->file('profile_photo')->store('profile_photos/riders', 'public');
                }

                $rider = Rider::create($data);
                $rider->statistics()->create([]);

                $token = $rider->createToken('auth_token')->plainTextToken;
                $rider->update(['session_token' => $token]);

                return response()->json([
                    'status'  => 'success',
                    'message' => 'Rider registered successfully',
                    'access_token' => $token,
                    'user' => $rider->load('statistics')
                ], 201);
            });
        } catch (Exception $e) {
            Log::error('Rider Reg Error: ' . $e->getMessage());
            return response()->json(['status' => 'error', 'message' => 'Registration failed.'], 500);
        }
    }

    public function checkDriverPhone(Request $request)
    {
        try {
            $exists = Driver::where('mobile_number', $request->mobile_number)->exists();
            return response()->json([
                'status' => 'success',
                'exists' => $exists,
                'message' => $exists ? 'Number already registered.' : 'Number available.'
            ], $exists ? 409 : 200);
        } catch (Exception $e) {
            return response()->json(['status' => 'error', 'message' => 'Server error.'], 500);
        }
    }

    public function checkRiderPhone(Request $request)
    {
        try {
            $exists = Rider::where('mobile_number', $request->mobile_number)->exists();
            return response()->json([
                'status' => 'success',
                'exists' => $exists,
                'message' => $exists ? 'Number already registered.' : 'Number available.'
            ], $exists ? 409 : 200);
        } catch (Exception $e) {
            return response()->json(['status' => 'error', 'message' => 'Server error.'], 500);
        }
    }
}
