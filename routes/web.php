<?php

use App\Http\Controllers\ProfileController;
use App\Http\Controllers\MessengerController; // Add this line
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "web" middleware group. Make something great!
|
*/

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
});

Route::get('/messages/{userId?}', [MessengerController::class, 'showMessages'])->name('messages.show');
Route::post('/send-message', [MessengerController::class, 'sendMessage'])->name('messages.send');
Route::get('/webhook', [MessengerController::class, 'verifyWebhook']);
Route::post('/webhook', [MessengerController::class, 'handleWebhook']);
Route::get('/messages', [MessengerController::class, 'showMessages']);
Route::post('/send-message', [MessengerController::class, 'sendMessage'])->name('send.message');
Route::get('/messages/{userId?}', [MessengerController::class, 'showMessages'])->name('messages');
//lấy tin nhắn mới nhất
Route::get('/messages/{userId}/latest', [MessengerController::class, 'getLatestMessages']);

// Authentication Routes
Route::get('/login', [\App\Http\Controllers\Auth\AuthenticatedSessionController::class, 'create'])->name('login');
Route::post('/login', [\App\Http\Controllers\Auth\AuthenticatedSessionController::class, 'store']);
Route::post('/logout', [\App\Http\Controllers\Auth\AuthenticatedSessionController::class, 'destroy'])->name('logout');
Route::get('/register', [\App\Http\Controllers\Auth\RegisteredUserController::class, 'create'])->name('register');
Route::post('/register', [\App\Http\Controllers\Auth\RegisteredUserController::class, 'store']);
