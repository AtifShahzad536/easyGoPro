<?php

namespace App\Http\Controllers\Admin\Settings;

use App\Http\Controllers\Controller;
use Exception;

class ReviewController extends Controller
{
    public function index()
    {
        try {
            return view('admin.settings.reviews.index');
        } catch (Exception $e) {
            return back()->with('error', 'Unable to load reviews.');
        }
    }
}
