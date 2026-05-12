<?php

namespace App\Http\Controllers\Admin\Settings;

use App\Http\Controllers\Controller;
use Exception;

class ReportController extends Controller
{
    public function index()
    {
        try {
            return view('admin.settings.reports.index');
        } catch (Exception $e) {
            return back()->with('error', 'Unable to load reports.');
        }
    }
}
