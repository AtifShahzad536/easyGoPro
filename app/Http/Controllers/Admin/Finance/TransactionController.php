<?php

namespace App\Http\Controllers\Admin\Finance;

use App\Http\Controllers\Controller;
use Exception;

class TransactionController extends Controller
{
    public function index()
    {
        try {
            return view('admin.finance.transactions.index');
        } catch (Exception $e) {
            return back()->with('error', 'Unable to load transactions.');
        }
    }
}
