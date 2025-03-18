<?php

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
use App\Http\Controllers\MessengerController;

Route::get('/webhook', [MessengerController::class, 'verifyWebhook']);
Route::post('/webhook', [MessengerController::class, 'handleWebhook']);
Route::get('/messages', [MessengerController::class, 'showMessages']);
Route::post('/send-message', [MessengerController::class, 'sendMessage'])->name('send.message');
