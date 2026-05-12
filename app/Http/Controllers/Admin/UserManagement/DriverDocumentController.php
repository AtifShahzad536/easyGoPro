<?php

namespace App\Http\Controllers\Admin\UserManagement;

use App\Http\Controllers\Controller;
use App\Models\Driver;
use App\Models\DriverDocument;
use Illuminate\Http\Request;
use Exception;
use Illuminate\Support\Facades\Log;

class DriverDocumentController extends Controller
{
    /**
     * List all driver documents (pending verification).
     */
    public function index()
    {
        try {
            $documents = DriverDocument::with(['driver'])
                ->where('status', 'pending')
                ->latest()
                ->paginate(20);
            
            return view('admin.users.documents.index', compact('documents'));
        } catch (Exception $e) {
            Log::error('Admin Document Index Error: ' . $e->getMessage());
            return back()->with('error', 'Failed to load documents.');
        }
    }

    /**
     * Show all documents of a specific driver.
     */
    public function showByDriver(Driver $driver)
    {
        try {
            $documents = $driver->documents;
            return view('admin.users.documents.show', compact('driver', 'documents'));
        } catch (Exception $e) {
            return back()->with('error', 'Documents not found for this driver.');
        }
    }

    /**
     * Approve a driver document.
     */
    public function approve($id)
    {
        try {
            $document = DriverDocument::findOrFail($id);
            $document->update([
                'status' => 'verified',
                'rejection_reason' => null
            ]);

            $message = 'Document approved successfully.';
            if (request()->expectsJson() || request()->ajax()) {
                return response()->json(['status' => 'success', 'message' => $message]);
            }

            return back()->with('success', $message);
        } catch (Exception $e) {
            return response()->json(['status' => 'error', 'message' => 'Approval failed.'], 500);
        }
    }

    /**
     * Reject a driver document with a reason.
     */
    public function reject(Request $request, $id)
    {
        try {
            $request->validate([
                'reason' => 'required|string|max:500',
            ]);

            $document = DriverDocument::findOrFail($id);
            $document->update([
                'status' => 'rejected',
                'rejection_reason' => $request->reason
            ]);

            $message = 'Document rejected with reason.';
            if ($request->expectsJson() || $request->ajax()) {
                return response()->json(['status' => 'success', 'message' => $message]);
            }

            return back()->with('success', $message);
        } catch (Exception $e) {
            return response()->json(['status' => 'error', 'message' => 'Rejection failed.'], 500);
        }
    }
}
