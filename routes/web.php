<?php

use App\Http\Controllers\PersonController;
use App\Http\Controllers\PrintoutController;
use App\Http\Middleware\SetLocale;
use Illuminate\Support\Facades\Route;

// The app itself (dashboard etc.) is the Filament panel mounted at "/".
// There is no sign-in: "login" is the "Who are you?" page.

Route::middleware('guest')->group(function () {
    Route::get('/setup', [PersonController::class, 'setup'])->name('setup');
    Route::post('/setup', [PersonController::class, 'storeSetup'])->name('setup.store');

    Route::get('/who', [PersonController::class, 'choose'])->name('login');
    Route::post('/who', [PersonController::class, 'store'])->name('people.store');
    Route::post('/who/{user}', [PersonController::class, 'select'])->name('people.select');
});

// Paper emergency copy (printout): a plain page outside the panel, in the person's language.
Route::middleware(['auth', SetLocale::class])
    ->get('/printout', PrintoutController::class)
    ->name('printout');
