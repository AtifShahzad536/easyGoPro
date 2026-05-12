<?php

namespace App\Http\Controllers\Admin\Finance;

use App\Http\Controllers\Controller;
use Exception;

class WalletController extends Controller
{
    public function index()
    {
        try {
            return view('admin.finance.wallets.index');
        } catch (Exception $e) {
            return back()->with('error', 'Unable to load wallets.');
        }
    }
}
