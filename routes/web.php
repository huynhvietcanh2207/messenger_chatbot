<?php

use App\Http\Controllers\ProfileController;
use App\Http\Controllers\MessengerController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

Route::get('/dashboard', function () {
    return view('dashboard');
})->middleware(['auth', 'verified'])->name('dashboard');

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');

    Route::get('/messages/{userId?}', [MessengerController::class, 'showMessages'])->name('messages.show');
    Route::post('/send-message', [MessengerController::class, 'sendMessage'])->name('messages.send');
    Route::get('/messages/{userId}/latest', [MessengerController::class, 'getLatestMessages']);
});

Route::get('/webhook', [MessengerController::class, 'verifyWebhook']);
Route::post('/webhook', [MessengerController::class, 'handleWebhook']);

// Authentication Routes
Route::get('/login', [\App\Http\Controllers\Auth\AuthenticatedSessionController::class, 'create'])->name('login');
Route::post('/login', [\App\Http\Controllers\Auth\AuthenticatedSessionController::class, 'store']);
Route::post('/logout', [\App\Http\Controllers\Auth\AuthenticatedSessionController::class, 'destroy'])->name('logout');
Route::get('/register', [\App\Http\Controllers\Auth\RegisteredUserController::class, 'create'])->name('register');
Route::post('/register', [\App\Http\Controllers\Auth\RegisteredUserController::class, 'store']);