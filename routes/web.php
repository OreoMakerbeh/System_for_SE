<?php

use App\Http\Controllers\AdminController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\DeliveryUpdateController;
use App\Http\Controllers\NotificationController;
use App\Http\Controllers\OrderController;
use Illuminate\Support\Facades\Route;

Route::redirect('/', '/dashboard');

Route::middleware('guest')->group(function () {
    Route::get('/login', [AuthController::class, 'showLogin'])->name('login');
    Route::post('/login', [AuthController::class, 'login'])->name('login.store');
    Route::post('/login/captcha/verify', [AuthController::class, 'verifyLoginCaptcha'])->name('login.captcha.verify');
    Route::get('/register', [AuthController::class, 'showRegister'])->name('register');
    Route::post('/register', [AuthController::class, 'register'])->name('register.store');
});

Route::match(['get', 'post'], '/logout', [AuthController::class, 'logout'])->middleware('auth')->name('logout');

Route::middleware('auth')->group(function () {
    Route::get('/dashboard', DashboardController::class)->name('dashboard');
    Route::get('/notifications/unread', [NotificationController::class, 'unread'])->name('notifications.unread');
    Route::delete('/notifications/{notification}', [NotificationController::class, 'destroy'])->name('notifications.destroy');
    Route::get('/account/password', [AuthController::class, 'showChangePassword'])->name('password.edit');
    Route::put('/account/password', [AuthController::class, 'changePassword'])->name('password.update');
    Route::get('/orders/create', [OrderController::class, 'create'])->name('orders.create');
    Route::post('/orders', [OrderController::class, 'store'])->name('orders.store');
    Route::get('/orders/{order}', [OrderController::class, 'show'])->name('orders.show');
    Route::patch('/rider/orders/{order}/claim', [DeliveryUpdateController::class, 'claim'])->name('rider.orders.claim');
    Route::post('/rider/orders/{order}/updates', [DeliveryUpdateController::class, 'store'])->name('rider.orders.updates.store');
    Route::patch('/orders/{order}/cancel', [OrderController::class, 'cancel'])->name('orders.cancel');
    Route::delete('/orders/{order}', [OrderController::class, 'destroy'])->name('orders.destroy');

    Route::prefix('admin')->name('admin.')->group(function () {
        Route::get('/orders', [AdminController::class, 'orders'])->name('orders');
        Route::get('/riders', [AdminController::class, 'riders'])->name('riders');
        Route::post('/riders', [AdminController::class, 'storeRider'])->name('riders.store');
        Route::get('/orders/{order}/edit', [AdminController::class, 'editOrder'])->name('orders.edit');
        Route::patch('/orders/{order}/edit', [AdminController::class, 'saveOrder'])->name('orders.save');
        Route::get('/archive', [AdminController::class, 'archive'])->name('archive');
        Route::patch('/orders/{order}/archive', [AdminController::class, 'archiveOrder'])->name('orders.archive');
        Route::delete('/archive/{order}', [AdminController::class, 'deleteArchivedOrder'])->name('archive.delete');
        Route::get('/customers', [AdminController::class, 'customers'])->name('customers');
        Route::get('/approvals', [AdminController::class, 'approvals'])->name('approvals');
        Route::patch('/approvals/{user}', [AdminController::class, 'approveCustomer'])->name('approvals.approve');
        Route::patch('/customers/{user}', [AdminController::class, 'updateCustomer'])->name('customers.update');
        Route::delete('/customers/{user}', [AdminController::class, 'deleteCustomer'])->name('customers.delete');
        Route::get('/administrators', [AdminController::class, 'administrators'])->name('administrators');
        Route::post('/administrators', [AdminController::class, 'storeAdministrator'])->name('administrators.store');
        Route::patch('/administrators/{user}', [AdminController::class, 'updateAdministrator'])->name('administrators.update');
        Route::patch('/administrators/{user}/revoke', [AdminController::class, 'revokeAdministrator'])->name('administrators.revoke');
        Route::patch('/orders/{order}', [AdminController::class, 'updateOrder'])->name('orders.update');
    });
});
