<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\Driver;
use Illuminate\Http\Request;
use Exception;

class DriverStatusController extends Controller
{
    /**
     * Display drivers online/offline status dashboard
     */
    public function index()
    {
        try {
            $onlineDrivers = Driver::where('status', 'online')->count();
            $offlineDrivers = Driver::where('status', 'offline')->count();
            $onTripDrivers = Driver::where('status', 'on_trip')->count();
            
            $drivers = Driver::latest()->paginate(20);

            return view('admin.driver-status.index', compact('onlineDrivers', 'offlineDrivers', 'onTripDrivers', 'drivers'));
        } catch (Exception $e) {
            return back()->with('error', 'Failed to load driver status dashboard.');
        }
    }
}
