<?php

namespace App\Http\Controllers\Admin\UserManagement;

use App\Http\Controllers\Controller;
use App\Models\Driver;
use Illuminate\Http\Request;
use Exception;
use Illuminate\Support\Facades\Log;

class DriverController extends Controller
{
    /**
     * Display a listing of all drivers.
     */
    public function index()
    {
        try {
            $drivers = Driver::with(['documents', 'vehicle', 'statistics'])
                ->latest()
                ->paginate(15);
            
            return view('admin.users.drivers.index', compact('drivers'));
        } catch (Exception $e) {
            Log::error('Admin Driver Index Error: ' . $e->getMessage());
            return back()->with('error', 'Unable to load drivers list.');
        }
    }

    /**
     * Show driver details.
     */
    public function show(Driver $driver)
    {
        try {
            $driver->load(['documents', 'vehicle', 'statistics']);
            return view('admin.users.drivers.show', compact('driver'));
        } catch (Exception $e) {
            return back()->with('error', 'Driver details not found.');
        }
    }

    /**
     * Suspend driver.
     */
    public function suspend(Driver $driver)
    {
        try {
            $driver->update(['status' => 'suspended']);
            return redirect()->route('drivers.index')->with('success', 'Driver suspended successfully.');
        } catch (Exception $e) {
            return back()->with('error', 'Failed to suspend driver.');
        }
    }

    /**
     * Activate driver.
     */
    public function activate(Driver $driver)
    {
        try {
            $driver->update(['status' => 'offline']);
            return redirect()->route('drivers.index')->with('success', 'Driver activated successfully.');
        } catch (Exception $e) {
            return back()->with('error', 'Failed to activate driver.');
        }
    }

    /**
     * Update driver details (AJAX/Post)
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

            $driver->update($request->only(['full_name', 'mobile_number', 'status', 'kyc_status']));

            if ($request->has('vehicle_model') && $driver->vehicle) {
                $driver->vehicle->update(['model' => $request->vehicle_model]);
            }

            return response()->json([
                'status' => 'success',
                'message' => 'Driver updated successfully',
                'driver' => $driver->load(['vehicle'])
            ]);
        } catch (Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Update failed: ' . $e->getMessage()
            ], 500);
        }
    }
}
