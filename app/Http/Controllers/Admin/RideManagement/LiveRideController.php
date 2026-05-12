<?php

namespace App\Http\Controllers\Admin\RideManagement;

use App\Http\Controllers\Controller;
use App\Models\Ride;
use Exception;
use Illuminate\Support\Facades\Log;

class LiveRideController extends Controller
{
    /**
     * Display all active/live rides.
     */
    public function index()
    {
        try {
            $rides = Ride::with(['rider', 'driver'])
                ->whereIn('status', ['searching', 'accepted', 'ongoing'])
                ->latest()
                ->paginate(20);
            
            return view('admin.rides.live.index', compact('rides'));
        } catch (Exception $e) {
            Log::error('Admin LiveRide Index Error: ' . $e->getMessage());
            return back()->with('error', 'Unable to load live rides.');
        }
    }

    /**
     * Show live ride details with tracking.
     */
    public function show(Ride $ride)
    {
        try {
            $ride->load(['rider', 'driver', 'stops']);
            return view('admin.rides.live.show', compact('ride'));
        } catch (Exception $e) {
            return back()->with('error', 'Ride details not found.');
        }
    }
}
