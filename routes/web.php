<?php

use App\Http\Controllers\DashboardController;
use App\Http\Middleware\EnsureAdministrator;
use Illuminate\Support\Facades\Route;

Route::redirect('/', '/dashboard');

require __DIR__.'/auth.php';

Route::middleware('auth')->group(function () {
    Route::get('/dashboard', DashboardController::class)->name('dashboard');

    require __DIR__.'/customer.php';
    require __DIR__.'/rider.php';

    Route::prefix('admin')
        ->name('admin.')
        ->middleware(EnsureAdministrator::class)
        ->group(__DIR__.'/admin.php');
});
