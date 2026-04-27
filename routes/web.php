<?php

use App\Http\Controllers\ProfileController;
use App\Http\Controllers\Web\DashboardController;
use App\Http\Controllers\Web\DriverStatusController;
use App\Http\Controllers\Web\FileController;
use App\Http\Controllers\Web\SettingsController;
use App\Http\Controllers\Admin\UserManagement\DriverController;
use App\Http\Controllers\Admin\UserManagement\RiderController;
use App\Http\Controllers\Admin\UserManagement\DriverDocumentController;
use App\Http\Controllers\Admin\RideManagement\LiveRideController;
use App\Http\Controllers\Admin\RideManagement\RideHistoryController;
use App\Http\Controllers\Admin\RideManagement\ScheduledRideController;
use App\Http\Controllers\Admin\Finance\TransactionController;
use App\Http\Controllers\Admin\Finance\PayoutController;
use App\Http\Controllers\Admin\Finance\WalletController;
use App\Http\Controllers\Admin\Settings\PromotionController;
use App\Http\Controllers\Admin\Settings\ReviewController;
use App\Http\Controllers\Admin\Settings\ReportController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    //hadi sir  ko biwi na mily jinho ny github per uplaod krny ko bola
    if (auth()->check()) {
            return redirect('/dashboard');
    }
    return view('auth.login');
});

// Driver Document Approval/Rejection (accessible from drivers page)
Route::post('/driver-documents/{id}/approve', [DriverDocumentController::class, 'approve'])->name('admin.documents.approve')->middleware('auth');
Route::post('/driver-documents/{id}/reject', [DriverDocumentController::class, 'reject'])->name('admin.documents.reject')->middleware('auth');

Route::prefix('admin')->middleware('auth')->group(function () {
    // Admin routes can be added here if needed
});

Route::get('/dashboard', function () {
    return view('dashboard');
})->middleware(['auth', 'verified'])->name('dashboard');

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
    
    Route::get('/riders', [RiderController::class, 'index'])->name('riders.index');
    Route::patch('/riders/{rider}/status', [RiderController::class, 'updateStatus'])->name('riders.status');
    Route::get('/drivers', [DriverController::class, 'index'])->name('drivers.index');
    Route::get('/drivers/{driver}', [DriverController::class, 'show'])->name('drivers.show');
    Route::patch('/drivers/{driver}', [DriverController::class, 'update'])->name('drivers.update');
    Route::post('/drivers/{driver}/suspend', [DriverController::class, 'suspend'])->name('drivers.suspend');
    Route::post('/drivers/{driver}/activate', [DriverController::class, 'activate'])->name('drivers.activate');
    Route::get('/live-rides', [LiveRideController::class, 'index'])->name('live-rides.index');
    Route::get('/ride-history', [RideHistoryController::class, 'index'])->name('ride-history.index');
    Route::get('/scheduled-rides', [ScheduledRideController::class, 'index'])->name('scheduled-rides.index');
    Route::get('/transactions', [TransactionController::class, 'index'])->name('transactions.index');
    Route::get('/payouts', [PayoutController::class, 'index'])->name('payouts.index');
    Route::get('/wallets', [WalletController::class, 'index'])->name('wallets.index');
    Route::get('/promotions', [PromotionController::class, 'index'])->name('promotions.index');
    Route::get('/reviews', [ReviewController::class, 'index'])->name('reviews.index');
    Route::get('/reports', [ReportController::class, 'index'])->name('reports.index');
    Route::get('/driver-status', [DriverStatusController::class, 'index'])->name('driver-status.index');
    Route::get('/settings', [SettingsController::class, 'index'])->name('settings.index');
});

// Profile photo serving route (for mobile app)
Route::get('/storage/profile_photos/{path}', [FileController::class, 'serveProfilePhoto'])
    ->where('path', '.*')
    ->name('profile.photo.serve');

require __DIR__.'/auth.php';
