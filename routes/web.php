<?php

use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\Auth\RegisterController;
use App\Http\Controllers\PtosrVerificationController;
use App\Http\Controllers\ReportController;
use App\Http\Controllers\TicketController;
use App\Http\Controllers\VesselController;
use Illuminate\Support\Facades\Route;

Route::middleware('guest')->group(function () {
    Route::get('/login', [LoginController::class, 'create'])->name('login');
    Route::post('/login', [LoginController::class, 'store'])->middleware('throttle:10,1')->name('login.store');
    Route::get('/register', [RegisterController::class, 'create'])->name('register');
    Route::post('/register', [RegisterController::class, 'store'])->middleware('throttle:5,1')->name('register.store');
});

Route::middleware('auth')->group(function () {
    Route::post('/logout', [LoginController::class, 'destroy'])->name('logout');

    Route::get('/', [TicketController::class, 'create'])->name('tickets.create');
    Route::get('/vessels', [VesselController::class, 'index'])->name('vessels.index');

    Route::get('/tickets', [TicketController::class, 'index'])->name('tickets.index');
    Route::post('/tickets', [TicketController::class, 'store'])->name('tickets.store');
    Route::get('/tickets/{ticket}/print', [TicketController::class, 'print'])->name('tickets.print');
    Route::post('/tickets/{ticket}/reprint', [TicketController::class, 'reprint'])->name('tickets.reprint');
    Route::post('/tickets/{ticket}/photos', [TicketController::class, 'updatePhotos'])->name('tickets.photos');
    Route::get('/tickets/{ticket}', [TicketController::class, 'show'])->name('tickets.show');
    Route::post('/tickets/{ticket}/ptosr-verification', [PtosrVerificationController::class, 'store'])->name('tickets.ptosr.store');
    Route::delete('/tickets/{ticket}/ptosr-verification', [PtosrVerificationController::class, 'destroy'])->name('tickets.ptosr.destroy');

    Route::get('/reports', [ReportController::class, 'vessels'])->name('reports.vessels');
    Route::get('/reports/vessels/{voyageNo}', [ReportController::class, 'vessel'])->name('reports.vessel');
    Route::get('/reports/vehicles', [ReportController::class, 'vehicles'])->name('reports.vehicles');
    Route::get('/reports/export', [ReportController::class, 'export'])->name('reports.export');
});
