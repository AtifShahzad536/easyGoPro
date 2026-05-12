<?php

namespace App\Http\Controllers\Admin\UserManagement;

use App\Http\Controllers\Controller;
use App\Models\Rider;
use Illuminate\Http\Request;
use Exception;
use Illuminate\Support\Facades\Log;

class RiderController extends Controller
{
    /**
     * Display a listing of all riders.
     */
    public function index()
    {
        try {
            $riders = Rider::with('statistics')
                ->latest()
                ->paginate(15);
            
            return view('admin.users.riders.index', compact('riders'));
        } catch (Exception $e) {
            Log::error('Admin Rider Index Error: ' . $e->getMessage());
            return back()->with('error', 'Unable to load riders.');
        }
    }

    /**
     * Show rider details.
     */
    public function show(Rider $rider)
    {
        try {
            $rider->load('statistics');
            return view('admin.users.riders.show', compact('rider'));
        } catch (Exception $e) {
            return back()->with('error', 'Rider not found.');
        }
    }

    /**
     * Update rider status (AJAX)
     */
    public function updateStatus(Request $request, Rider $rider)
    {
        try {
            $request->validate([
                'status' => 'required|in:active,banned,inactive'
            ]);

            $rider->update(['status' => $request->status]);

            return response()->json([
                'status' => 'success',
                'message' => 'Rider status updated successfully',
                'rider' => $rider
            ]);
        } catch (Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Status update failed.'
            ], 500);
        }
    }
}
