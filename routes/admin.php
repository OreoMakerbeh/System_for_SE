<?php

use App\Http\Controllers\Admin\AdministratorController;
use App\Http\Controllers\Admin\ApprovalController;
use App\Http\Controllers\Admin\ArchiveController;
use App\Http\Controllers\Admin\CustomerController;
use App\Http\Controllers\Admin\OrderController;
use App\Http\Controllers\Admin\RiderController;
use Illuminate\Support\Facades\Route;

Route::get('/orders', [OrderController::class, 'orders'])->name('orders');
Route::get('/orders/{order}/edit', [OrderController::class, 'editOrder'])->name('orders.edit');
Route::patch('/orders/{order}/edit', [OrderController::class, 'saveOrder'])->name('orders.save');
Route::patch('/orders/{order}', [OrderController::class, 'updateOrder'])->name('orders.update');

Route::get('/archive', [ArchiveController::class, 'archive'])->name('archive');
Route::patch('/orders/{order}/archive', [ArchiveController::class, 'archiveOrder'])->name('orders.archive');
Route::delete('/archive/{order}', [ArchiveController::class, 'deleteArchivedOrder'])->name('archive.delete');

Route::get('/customers', [CustomerController::class, 'customers'])->name('customers');
Route::patch('/customers/{user}', [CustomerController::class, 'updateCustomer'])->name('customers.update');
Route::delete('/customers/{user}', [CustomerController::class, 'deleteCustomer'])->name('customers.delete');

Route::get('/approvals', [ApprovalController::class, 'approvals'])->name('approvals');
Route::patch('/approvals/{user}', [ApprovalController::class, 'approveCustomer'])->name('approvals.approve');

Route::get('/riders', [RiderController::class, 'riders'])->name('riders');
Route::post('/riders', [RiderController::class, 'storeRider'])->name('riders.store');

Route::get('/administrators', [AdministratorController::class, 'administrators'])->name('administrators');
Route::post('/administrators', [AdministratorController::class, 'storeAdministrator'])->name('administrators.store');
Route::patch('/administrators/{user}', [AdministratorController::class, 'updateAdministrator'])->name('administrators.update');
Route::patch('/administrators/{user}/revoke', [AdministratorController::class, 'revokeAdministrator'])->name('administrators.revoke');
