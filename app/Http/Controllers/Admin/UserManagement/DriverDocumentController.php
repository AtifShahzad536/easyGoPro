<?php

namespace App\Http\Controllers\Admin\UserManagement;

use App\Http\Controllers\Controller;
use App\Models\Driver;
use App\Models\DriverDocument;
use Illuminate\Http\Request;

class DriverDocumentController extends Controller
{
    /**
     * List all driver documents (pending verification).
     */
    public function index()
    {
        $documents = DriverDocument::with(['driver'])
            ->where('status', 'pending')
            ->latest()
            ->paginate(20);
        
        return view('admin.users.documents.index', compact('documents'));
    }

    /**
     * Show all documents of a specific driver.
     */
    public function showByDriver(Driver $driver)
    {
        $documents = $driver->documents;
        return view('admin.users.documents.show', compact('driver', 'documents'));
    }

    /**
     * Approve a driver document.
     */
    public function approve($id)
    {
        $document = DriverDocument::findOrFail($id);
        $document->update([
            'status' => 'verified',
            'rejection_reason' => null
        ]);

        if (request()->expectsJson() || request()->ajax()) {
            return response()->json(['success' => true, 'message' => 'Document approved successfully.']);
        }

        return back()->with('success', 'Document approved successfully.');
    }

    /**
     * Reject a driver document with a reason.
     */
    public function reject(Request $request, $id)
    {
        $request->validate([
            'reason' => 'required|string|max:500',
        ]);

        $document = DriverDocument::findOrFail($id);
        $document->update([
            'status' => 'rejected',
            'rejection_reason' => $request->reason
        ]);

        if ($request->expectsJson() || $request->ajax()) {
            return response()->json(['success' => true, 'message' => 'Document rejected.']);
        }

        return back()->with('success', 'Document rejected.');
    }
}
