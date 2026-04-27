<?php

namespace App\Http\Controllers\Admin\UserManagement;

use App\Http\Controllers\Controller;
use App\Models\Driver;
use Illuminate\Http\Request;

class DriverController extends Controller
{
    /**
     * Display a listing of all drivers.
     */
    public function index()
    {
        $drivers = Driver::with(['documents', 'vehicle', 'statistics'])
            ->latest()
            ->paginate(15);
        
        return view('admin.users.drivers.index', compact('drivers'));
    }

    /**
     * Show driver details.
     */
    public function show(Driver $driver)
    {
        $driver->load(['documents', 'vehicle', 'statistics']);
        return view('admin.users.drivers.show', compact('driver'));
    }

    /**
     * Suspend driver.
     */
    public function suspend(Driver $driver)
    {
        $driver->status = 'suspended';
        $driver->save();

        return redirect()->route('drivers.index')->with('success', 'Driver suspended successfully.');
    }

    /**
     * Activate driver.
     */
    public function activate(Driver $driver)
    {
        $driver->status = 'offline';
        $driver->save();

        return redirect()->route('drivers.index')->with('success', 'Driver activated successfully.');
    }

    /**
     * Update driver KYC status.
     */
    public function updateKycStatus(Request $request, Driver $driver)
    {
        $request->validate([
            'kyc_status' => 'required|in:pending,verified,rejected'
        ]);

        $driver->kyc_status = $request->kyc_status;
        $driver->save();

        return redirect()->route('drivers.index')->with('success', 'KYC status updated successfully.');
    }

    /**
     * Update driver details via AJAX.
     */
    public function update(Request $request, Driver $driver)
    {
        try {
            $request->validate([
                'full_name' => 'required|string|max:255',
                'mobile_number' => 'required|string|max:20',
                'status' => 'required|in:online,offline,on_trip,suspended',
                'kyc_status' => 'required|in:pending,in_review,approved,rejected',
            ]);

            $driver->full_name = $request->full_name;
            $driver->mobile_number = $request->mobile_number;
            $driver->status = $request->status;
            $driver->kyc_status = $request->kyc_status;
            $driver->save();

            // Update vehicle model if provided
            if ($request->has('vehicle_model') && $driver->vehicle) {
                $driver->vehicle->model = $request->vehicle_model;
                $driver->vehicle->save();
            }

            return response()->json([
                'status' => 'success',
                'message' => 'Driver updated successfully',
                'driver' => $driver->load(['vehicle'])
            ]);
        } catch (\Illuminate\Database\QueryException $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Database error: ' . $e->getMessage()
            ], 500);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => $e->getMessage()
            ], 500);
        }
    }
}
