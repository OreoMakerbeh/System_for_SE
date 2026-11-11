<?php

use App\Http\Controllers\Rider\DeliveryUpdateController;
use Illuminate\Support\Facades\Route;

Route::patch('/rider/orders/{order}/claim', [DeliveryUpdateController::class, 'claim'])->name('rider.orders.claim');
Route::post('/rider/orders/{order}/updates', [DeliveryUpdateController::class, 'store'])->name('rider.orders.updates.store');
