<?php

namespace App\Http\Controllers\Admin\Settings;

use App\Http\Controllers\Controller;
use Exception;

class PromotionController extends Controller
{
    public function index()
    {
        try {
            return view('admin.settings.promotions.index');
        } catch (Exception $e) {
            return back()->with('error', 'Unable to load promotions.');
        }
    }
}
