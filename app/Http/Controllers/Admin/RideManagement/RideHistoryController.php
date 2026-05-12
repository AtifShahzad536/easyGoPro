<?php

namespace App\Http\Controllers\Admin\RideManagement;

use App\Http\Controllers\Controller;
use App\Models\Ride;
use Exception;
use Illuminate\Support\Facades\Log;

class RideHistoryController extends Controller
{
    /**
     * Display completed and cancelled rides.
     */
    public function index()
    {
        try {
            $rides = Ride::with(['rider', 'driver'])
                ->whereIn('status', ['completed', 'cancelled'])
                ->latest()
                ->paginate(20);
            
            return view('admin.rides.history.index', compact('rides'));
        } catch (Exception $e) {
            Log::error('Admin RideHistory Index Error: ' . $e->getMessage());
            return back()->with('error', 'Unable to load ride history.');
        }
    }

    /**
     * Show ride details.
     */
    public function show(Ride $ride)
    {
        try {
            $ride->load(['rider', 'driver', 'stops']);
            return view('admin.rides.history.show', compact('ride'));
        } catch (Exception $e) {
            return back()->with('error', 'History record not found.');
        }
    }
}
