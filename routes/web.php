<?php

use App\Http\Controllers\Admin\DashboardController as AdminDashboardController;
use App\Http\Controllers\Admin\TicketController as AdminTicketController;
use App\Http\Controllers\Admin\UserController as AdminUserController;
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\MediaController;
use App\Http\Controllers\PtosrVerificationController;
use App\Http\Controllers\ReportController;
use App\Http\Controllers\TicketController;
use App\Http\Controllers\VesselController;
use App\Models\Ticket;
use Illuminate\Support\Facades\Route;

Route::middleware('guest')->group(function () {
    Route::get('/login', [LoginController::class, 'create'])->name('login');
    Route::post('/login', [LoginController::class, 'store'])->middleware('throttle:10,1')->name('login.store');
});

Route::middleware(['auth', 'active'])->group(function () {
    Route::post('/logout', [LoginController::class, 'destroy'])->name('logout');

    Route::get('/', [TicketController::class, 'create'])->name('tickets.create');
    Route::get('/vessels', [VesselController::class, 'index'])->name('vessels.index');

    Route::get('/tickets', [TicketController::class, 'index'])->name('tickets.index');
    Route::post('/tickets', [TicketController::class, 'store'])->name('tickets.store');
    Route::get('/tickets/{ticket}/print', [TicketController::class, 'print'])->name('tickets.print');
    Route::post('/tickets/{ticket}/reprint', [TicketController::class, 'reprint'])->name('tickets.reprint');
    // Photos are served by Laravel (no public/storage symlink needed), see MediaController.
    Route::get('/tickets/{ticket}/photos/{kind}', MediaController::class)
        ->whereIn('kind', array_keys(Ticket::PHOTO_KINDS))
        ->name('tickets.photo');
    Route::get('/tickets/{ticket}', [TicketController::class, 'show'])->name('tickets.show');
    Route::post('/tickets/{ticket}/ptosr-verification', [PtosrVerificationController::class, 'store'])->name('tickets.ptosr.store');
    Route::delete('/tickets/{ticket}/ptosr-verification', [PtosrVerificationController::class, 'destroy'])->name('tickets.ptosr.destroy');

    Route::get('/reports', [ReportController::class, 'vessels'])->name('reports.vessels');
    Route::get('/reports/vessels/{voyageNo}', [ReportController::class, 'vessel'])->name('reports.vessel');
    Route::get('/reports/vehicles', [ReportController::class, 'vehicles'])->name('reports.vehicles');
    Route::get('/reports/export', [ReportController::class, 'export'])->name('reports.export');

    Route::middleware('admin')->prefix('admin')->name('admin.')->group(function () {
        Route::get('/', [AdminDashboardController::class, 'index'])->name('dashboard');

        Route::resource('users', AdminUserController::class)->except('show');

        Route::get('/tickets', [AdminTicketController::class, 'index'])->name('tickets.index');
        Route::get('/tickets/{ticket}/edit', [AdminTicketController::class, 'edit'])->name('tickets.edit');
        Route::put('/tickets/{ticket}', [AdminTicketController::class, 'update'])->name('tickets.update');
        Route::delete('/tickets/{ticket}', [AdminTicketController::class, 'destroy'])->name('tickets.destroy');
    });
});
