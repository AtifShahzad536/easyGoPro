<?php

namespace App\Http\Controllers\Admin\Finance;

use App\Http\Controllers\Controller;
use Exception;

class PayoutController extends Controller
{
    public function index()
    {
        try {
            return view('admin.finance.payouts.index');
        } catch (Exception $e) {
            return back()->with('error', 'Unable to load payouts.');
        }
    }
}
