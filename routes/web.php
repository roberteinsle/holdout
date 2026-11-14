<?php

use App\Http\Controllers\Auth\CodeLoginController;
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\Auth\RegisterController;
use App\Http\Controllers\InvitationController;
use Illuminate\Support\Facades\Route;

// The app itself (dashboard etc.) is the Filament panel mounted at "/".

Route::middleware('guest')->group(function () {
    Route::get('/login', [LoginController::class, 'show'])->name('login');
    Route::post('/login', [LoginController::class, 'sendLink'])
        ->middleware('throttle:magic-link')
        ->name('login.send');
    Route::get('/login/sent', [LoginController::class, 'sent'])->name('login.sent');
    Route::get('/login/link/{token}', [LoginController::class, 'confirm'])
        ->where('token', '[A-Za-z0-9]{64}')
        ->name('login.confirm');
    Route::post('/login/link/{token}', [LoginController::class, 'consume'])
        ->where('token', '[A-Za-z0-9]{64}')
        ->middleware('throttle:token')
        ->name('login.consume');

    Route::get('/login/code', [CodeLoginController::class, 'show'])->name('login.code');
    Route::post('/login/code', [CodeLoginController::class, 'store'])
        ->middleware('throttle:code-login')
        ->name('login.code.store');

    Route::get('/register', [RegisterController::class, 'show'])->name('register');
    Route::post('/register', [RegisterController::class, 'store'])
        ->middleware('throttle:magic-link')
        ->name('register.store');

    Route::get('/invitations/{token}', [InvitationController::class, 'show'])
        ->where('token', '[A-Za-z0-9]{64}')
        ->name('invitations.show');
    Route::post('/invitations/{token}', [InvitationController::class, 'accept'])
        ->where('token', '[A-Za-z0-9]{64}')
        ->middleware('throttle:token')
        ->name('invitations.accept');
});
