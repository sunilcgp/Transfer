<?php

use App\Http\Controllers\Auth\AuthenticatedSessionController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\ScheduledTransferController;
use App\Http\Controllers\TransferController;
use App\Http\Controllers\TransferReportController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});
Route::post('/transfers', [TransferController::class, 'store'])->middleware('throttle:10,1')->name('transfers.store');
Route::get('/transfers/{token}', [TransferController::class, 'show'])->name('transfers.show');
Route::get('/transfers/{token}/files/{file}', [TransferController::class, 'download'])->name('transfers.download');
Route::post('/transfers/{token}/share-events', [TransferController::class, 'trackShare'])->middleware('throttle:30,1')->name('transfers.share.track');

Route::middleware('guest')->group(function (): void {
    Route::get('/login', [AuthenticatedSessionController::class, 'create'])->name('login');
    Route::post('/login', [AuthenticatedSessionController::class, 'store'])
        ->middleware('throttle:login')
        ->name('login.store');
});

Route::middleware('auth')->group(function (): void {
    Route::get('/dashboard', DashboardController::class)->name('dashboard');
    Route::get('/reports', [TransferReportController::class, 'index'])->name('reports.index');
    Route::get('/reports/data', [TransferReportController::class, 'data'])->name('reports.data');
    Route::get('/reports/files/{file}', [TransferReportController::class, 'viewFile'])->name('reports.files.view');
    Route::post('/reports/{transfer}/resend', [TransferReportController::class, 'resend'])->middleware('throttle:5,1')->name('reports.resend');
    Route::get('/scheduled', [ScheduledTransferController::class, 'index'])->name('scheduled.index');
    Route::post('/logout', [AuthenticatedSessionController::class, 'destroy'])->name('logout');
});
