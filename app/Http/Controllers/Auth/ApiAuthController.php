<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\Driver;
use App\Models\Rider;
use App\Models\Vehicle;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Throwable;

class ApiAuthController extends Controller
{
    /**
     * Register a new Driver or Rider.
     * Routes to the correct table based on 'role' field.
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
                'message' => 'Invalid role. Must be "driver" or "rider".',
            ], 422);
        } catch (Throwable $e) {
            Log::error('Registration error: ' . $e->getMessage(), ['trace' => $e->getTraceAsString()]);
            return response()->json([
                'status'  => 'error',
                'message' => 'An error occurred during registration. Please try again.',
            ], 500);
        }
    }

    /**
     * Login a Driver or Rider.
     * Routes to the correct table based on 'role' field.
     */
    public function login(Request $request)
    {
        try {
            DB::beginTransaction();

            $validator = Validator::make($request->all(), [
                'mobile_number' => 'required|string',
                'role'          => 'required|in:rider,driver',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'status'  => 'error',
                    'message' => 'Validation failed',
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
            // Check if driver is suspended
            if ($request->role === 'driver' && $user->status === 'suspended') {
                return response()->json([
                    'status'  => 'error',
                    'message' => 'Your account has been suspended. Please contact support.',
                ], 403);
            }

            // Check if rider is banned
            if ($request->role === 'rider' && $user->status === 'banned') {
                return response()->json([
                    'status'  => 'error',
                    'message' => 'Your account has been banned. Please contact support.',
                ], 403);
            }

            // Set driver status to online when logging in
            if ($request->role === 'driver') {
                $user->is_available = true;
                $user->save();
            }

            // Revoke old tokens and issue a new one
            $user->tokens()->delete();
            $token = $user->createToken('auth_token')->plainTextToken;

            // Store token in session_token column for legacy support
            $user->session_token = $token;
            // Store remember token for web remember me functionality
            $user->remember_token = Str::random(60);
            $user->save();

            DB::commit();

            // Prepare user data with full URL for profile photo
            $userData = $user->toArray();
            if ($user->profile_photo) {
                $userData['profile_photo_url'] = asset('storage/' . $user->profile_photo);
            }

            return response()->json([
                'status'       => 'success',
                'message'      => 'Login successful',
                'access_token' => $token,
                'token_type'   => 'Bearer',
                'role'         => $request->role,
                'user'         => $userData,
            ]);
        } catch (Throwable $e) {
            DB::rollBack();
            Log::error('Login error: ' . $e->getMessage(), ['trace' => $e->getTraceAsString()]);
            return response()->json([
                'status'  => 'error',
                'message' => 'An error occurred during login. Please try again.',
            ], 500);
        }
    }

    // ──────────────────────────────────────────────
    // Private Helpers
    // ──────────────────────────────────────────────

    private function registerDriver(Request $request)
    {
        $validator = Validator::make($request->all(), [
            // Driver Profile Data
            'full_name'      => 'required|string|max:255',
            'mobile_number'  => 'required|string|unique:drivers,mobile_number',
            'cnic'           => 'required|string|unique:drivers,cnic',
            'cnic_name'      => 'required|string|max:255',
            'email'          => 'nullable|email|unique:drivers,email',
            'date_of_birth'  => 'required|date',
            'gender'         => 'required|in:male,female,other',
            'profile_photo'  => 'nullable|image|max:2048',
            
            // Vehicle Data
            'vehicle'           => 'required|array',
            'vehicle.make'      => 'required|string|max:255',
            'vehicle.model'     => 'required|string|max:255',
            'vehicle.color'     => 'required|string|max:255',
            'vehicle.plate_number'=> 'required|string|max:20',
            'vehicle.type'      => 'required|in:bike,auto,economy_car,comfort_car',
            'vehicle.year'      => 'required|integer|min:2000|max:' . (date('Y') + 1),
            
            // Documents Data
            'documents'              => 'required|array',
            'documents.cnic_front' => 'required|file|mimes:jpg,jpeg,png,pdf|max:5120',
            'documents.cnic_back'    => 'required|file|mimes:jpg,jpeg,png,pdf|max:5120',
            'documents.license_front'=> 'required|file|mimes:jpg,jpeg,png,pdf|max:5120',
            'documents.license_back' => 'required|file|mimes:jpg,jpeg,png,pdf|max:5120',
            'documents.vehicle_registration' => 'required|file|mimes:jpg,jpeg,png,pdf|max:5120',
            'documents.insurance'  => 'required|file|mimes:jpg,jpeg,png,pdf|max:5120',
        ], [
            'vehicle.required' => 'Vehicle details are required for driver registration',
            'documents.required' => 'All documents are required for driver registration',
            'documents.*.required' => 'All documents are required',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status'  => 'error',
                'message' => 'Validation failed',
                'errors'  => $validator->errors(),
            ], 422);
        }

        try {
            return DB::transaction(function () use ($request) {
                // 1. Create Driver
                $data             = $request->except(['profile_photo', 'role', 'vehicle', 'documents']);
                $data['password'] = Hash::make(Str::random(16));
                $data['status']   = 'offline'; // Valid ENUM: offline, online, on_trip, suspended, banned
                $data['kyc_status'] = 'pending'; // Valid ENUM: pending, in_review, approved, rejected

                if ($request->hasFile('profile_photo')) {
                    $data['profile_photo'] = $request->file('profile_photo')->store('profile_photos/drivers', 'public');
                }

                $driver = Driver::create($data);

                // 2. Create Statistics Record
                $driver->statistics()->create([]);

                // 3. Create Vehicle
                $vehicleData = $request->input('vehicle');
                $vehicle = $driver->vehicle()->create([
                    'make'          => $vehicleData['make'],
                    'model'         => $vehicleData['model'],
                    'color'         => $vehicleData['color'],
                    'plate_number'  => $vehicleData['plate_number'],
                    'type'          => $vehicleData['type'],
                    'year'          => $vehicleData['year'],
                    'is_active'     => false, // Inactive until documents verified
                ]);

                // 4. Upload Documents
                $uploadedDocuments = [];
                $documentTypes = [
                    'cnic_front', 'cnic_back', 
                    'license_front', 'license_back',
                    'vehicle_registration', 'insurance'
                ];

                foreach ($documentTypes as $docType) {
                    if ($request->hasFile("documents.{$docType}")) {
                        $file = $request->file("documents.{$docType}");
                        $filename = $docType . '_' . time() . '.' . $file->getClientOriginalExtension();
                        $path = $file->storeAs("driver_documents/{$driver->id}", $filename, 'public');

                        $document = $driver->documents()->create([
                            'type'      => $docType,
                            'file_path' => $path,
                            'status'    => 'pending',
                        ]);

                        $uploadedDocuments[] = $document;
                    }
                }

                // 5. Generate Token
                $token = $driver->createToken('auth_token')->plainTextToken;
                $driver->session_token = $token;
                $driver->remember_token = Str::random(60);
                $driver->save();

                // Prepare response data
                $userData = $driver->load(['statistics', 'vehicle', 'documents'])->toArray();
                if ($driver->profile_photo) {
                    $userData['profile_photo_url'] = asset('storage/' . $driver->profile_photo);
                }

                return response()->json([
                    'status'       => 'success',
                    'message'      => 'Driver registered successfully with vehicle and documents',
                    'access_token' => $token,
                    'token_type'   => 'Bearer',
                    'role'         => 'driver',
                    'user'         => $userData,
                    'vehicle'      => $vehicle,
                    'registration_summary' => [
                        'driver_id'          => $driver->id,
                        'vehicle_id'         => $vehicle->id,
                        'documents_uploaded' => count($uploadedDocuments),
                        'driver_status'      => 'offline',
                        'kyc_status'         => 'pending',
                        'next_step'          => 'Wait for admin verification',
                    ],
                ], 201);
            });
        } catch (Throwable $e) {
            Log::error('Driver registration error: ' . $e->getMessage(), ['trace' => $e->getTraceAsString()]);
            return response()->json([
                'status'  => 'error',
                'message' => 'An error occurred during registration. Please try again.',
            ], 500);
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
            return response()->json([
                'status'  => 'error',
                'message' => 'Validation failed',
                'errors'  => $validator->errors(),
            ], 422);
        }

        try {
            return DB::transaction(function () use ($request) {
                $data             = $request->except(['profile_photo', 'role']);
                $data['password'] = Hash::make(Str::random(16));

                if ($request->hasFile('profile_photo')) {
                    $data['profile_photo'] = $request->file('profile_photo')->store('profile_photos/riders', 'public');
                }

                $rider = Rider::create($data);

                // Create empty statistics record for the rider
                $rider->statistics()->create([]);

                $token = $rider->createToken('auth_token')->plainTextToken;

                // Store token in session_token column
                $rider->session_token = $token;
                $rider->remember_token = Str::random(60);
                $rider->save();

                // Prepare user data with full URL for profile photo
                $userData = $rider->load('statistics')->toArray();
                if ($rider->profile_photo) {
                    $userData['profile_photo_url'] = asset('storage/' . $rider->profile_photo);
                }

                return response()->json([
                    'status'       => 'success',
                    'message'      => 'Rider registered successfully',
                    'access_token' => $token,
                    'token_type'   => 'Bearer',
                    'role'         => 'rider',
                    'user'         => $userData,
                ], 201);
            });
        } catch (Throwable $e) {
            Log::error('Rider registration error: ' . $e->getMessage(), ['trace' => $e->getTraceAsString()]);
            return response()->json([
                'status'  => 'error',
                'message' => 'An error occurred during registration. Please try again.',
            ], 500);
        }
    }

    /**
     * Step 3: Register vehicle details for authenticated driver.
     * Requires driver to be logged in (auth:sanctum).
     */
    public function registerVehicle(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'make'          => 'required|string|max:255',
            'model'         => 'required|string|max:255',
            'color'         => 'required|string|max:255',
            'plate_number'  => 'required|string|unique:vehicles,plate_number|max:20',
            'type'          => 'required|in:bike,auto,economy_car,comfort_car',
            'year'          => 'required|integer|min:2000|max:' . (date('Y') + 1),
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status'  => 'error',
                'message' => 'Validation failed',
                'errors'  => $validator->errors(),
            ], 422);
        }

        try {
            return DB::transaction(function () use ($request) {
                $driver = $request->user();

                // Check if user is a Driver, not a Rider
                if (!$driver instanceof \App\Models\Driver) {
                    return response()->json([
                        'status' => 'error',
                        'message' => 'Unauthorized. Only drivers can register vehicles.',
                    ], 403);
                }

                // Check if driver already has a vehicle
                if ($driver->vehicle) {
                    return response()->json([
                        'status'  => 'error',
                        'message' => 'Vehicle already registered. Use update endpoint to modify.',
                    ], 409);
                }

                $vehicle = $driver->vehicle()->create([
                    'make'          => $request->make,
                    'model'         => $request->model,
                    'color'         => $request->color,
                    'plate_number'  => $request->plate_number,
                    'type'          => $request->type,
                    'year'          => $request->year,
                    'is_active'     => true,
                ]);

                return response()->json([
                    'status'   => 'success',
                    'message'  => 'Vehicle registered successfully',
                    'vehicle'  => $vehicle,
                ], 201);
            });
        } catch (Throwable $e) {
            Log::error('Vehicle registration error: ' . $e->getMessage(), ['trace' => $e->getTraceAsString()]);
            return response()->json([
                'status'  => 'error',
                'message' => 'An error occurred during vehicle registration. Please try again.',
            ], 500);
        }
    }

    /**
     * Check if driver phone number exists
     */
    public function checkDriverPhone(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'mobile_number' => 'required|string',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'status'  => 'error',
                    'message' => 'Validation failed',
                    'errors'  => $validator->errors(),
                ], 422);
            }

            $exists = Driver::where('mobile_number', $request->mobile_number)->exists();

            if ($exists) {
                return response()->json([
                    'status'  => 'error',
                    'message' => 'This phone number is already registered. Please choose a different number.',
                    'exists'  => true,
                ], 409);
            }

            return response()->json([
                'status'  => 'success',
                'message' => 'Phone number is available',
                'exists'  => false,
            ], 200);
        } catch (Throwable $e) {
            Log::error('Driver phone check error: ' . $e->getMessage());
            return response()->json([
                'status'  => 'error',
                'message' => 'An error occurred. Please try again.',
            ], 500);
        }
    }

    /**
     * Check if rider phone number exists
     */
    public function checkRiderPhone(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'mobile_number' => 'required|string',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'status'  => 'error',
                    'message' => 'Validation failed',
                    'errors'  => $validator->errors(),
                ], 422);
            }

            $exists = Rider::where('mobile_number', $request->mobile_number)->exists();

            if ($exists) {
                return response()->json([
                    'status'  => 'error',
                    'message' => 'This phone number is already registered. Please choose a different number.',
                    'exists'  => true,
                ], 409);
            }

            return response()->json([
                'status'  => 'success',
                'message' => 'Phone number is available',
                'exists'  => false,
            ], 200);
        } catch (Throwable $e) {
            Log::error('Rider phone check error: ' . $e->getMessage());
            return response()->json([
                'status'  => 'error',
                'message' => 'An error occurred. Please try again.',
            ], 500);
        }
    }
}
