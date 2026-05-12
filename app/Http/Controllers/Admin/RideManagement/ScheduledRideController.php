<?php

namespace App\Http\Controllers\Admin\RideManagement;

use App\Http\Controllers\Controller;
use Exception;

class ScheduledRideController extends Controller
{
    /**
     * Display scheduled rides.
     */
    public function index()
    {
        try {
            return view('admin.rides.scheduled.index');
        } catch (Exception $e) {
            return back()->with('error', 'Unable to load scheduled rides.');
        }
    }
}
