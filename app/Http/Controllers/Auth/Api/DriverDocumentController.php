<?php

namespace App\Http\Controllers\Auth\Api;

use App\Http\Controllers\Controller;
use App\Models\DriverDocument;
use App\Models\Driver;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;
use Intervention\Image\ImageManager;
use Intervention\Image\Drivers\Gd\Driver as ImageDriver;
use Exception;

class DriverDocumentController extends Controller
{
    /**
     * Bulk upload all 6 driver documents at once.
     */
    public function bulkUpload(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'cnic'          => 'required|mimes:jpg,jpeg,png,pdf|max:10240',
                'license'       => 'required|mimes:jpg,jpeg,png,pdf|max:10240',
                'driver_photo'  => 'required|mimes:jpg,jpeg,png,pdf|max:10240',
                'registration'  => 'required|mimes:jpg,jpeg,png,pdf|max:10240',
                'insurance'     => 'required|mimes:jpg,jpeg,png,pdf|max:10240',
                'vehicle_photo' => 'required|mimes:jpg,jpeg,png,pdf|max:10240',
            ]);

            if ($validator->fails()) {
                return response()->json(['status' => 'error', 'errors' => $validator->errors()], 422);
            }

            $driver = $request->user();
            if (!$driver instanceof Driver) {
                return response()->json(['status' => 'error', 'message' => 'Unauthorized.'], 403);
            }

            $types = ['cnic', 'license', 'driver_photo', 'registration', 'insurance', 'vehicle_photo'];
            $uploaded = [];
            $manager  = new ImageManager(new ImageDriver());

            foreach ($types as $type) {
                $file = $request->file($type);
                $extension = strtolower($file->getClientOriginalExtension());
                $filename = $type . '_' . time() . '.' . $extension;
                $path = "driver_documents/{$driver->id}/{$filename}";

                if (in_array($extension, ['jpg', 'jpeg', 'png'])) {
                    $image = $manager->read($file->getPathname());
                    if ($image->width() > 1200) $image->scale(width: 1200);
                    $encoded = ($extension === 'png') ? $image->toPng() : $image->toJpeg(80);
                    Storage::disk('public')->put($path, (string) $encoded);
                } else {
                    Storage::disk('public')->putFileAs("driver_documents/{$driver->id}", $file, $filename);
                }

                $document = DriverDocument::updateOrCreate(
                    ['driver_id' => $driver->id, 'type' => $type],
                    ['file_path' => $path, 'status' => 'pending', 'rejection_reason' => null]
                );
                $uploaded[] = $document;
            }

            return response()->json([
                'status' => 'success',
                'message' => 'Documents uploaded and optimized.',
                'data' => $uploaded
            ]);

        } catch (Exception $e) {
            Log::error('Document Upload Error: ' . $e->getMessage());
            return response()->json(['status' => 'error', 'message' => 'Failed to upload documents.'], 500);
        }
    }
}
