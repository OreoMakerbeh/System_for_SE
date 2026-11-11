<?php

use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\Auth\PasswordController;
use App\Http\Controllers\Auth\RegistrationController;
use Illuminate\Support\Facades\Route;

Route::middleware('guest')->group(function () {
    Route::get('/login', [LoginController::class, 'showLogin'])->name('login');
    Route::post('/login', [LoginController::class, 'login'])->name('login.store');
    Route::post('/login/captcha/verify', [LoginController::class, 'verifyLoginCaptcha'])->name('login.captcha.verify');
    Route::get('/register', [RegistrationController::class, 'showRegister'])->name('register');
    Route::post('/register', [RegistrationController::class, 'register'])->name('register.store');
});

Route::middleware('auth')->group(function () {
    Route::match(['get', 'post'], '/logout', [LoginController::class, 'logout'])->name('logout');
    Route::get('/account/password', [PasswordController::class, 'showChangePassword'])->name('password.edit');
    Route::put('/account/password', [PasswordController::class, 'changePassword'])->name('password.update');
});
