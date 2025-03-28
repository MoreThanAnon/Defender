<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\PropertiesController;
use App\Http\Controllers\PropertyController;
use App\Http\Controllers\LoginController;
use App\Http\Controllers\StripeController;
use App\Http\Controllers\TestController;

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

Auth::routes();

Route::get('/register', [AuthController::class, 'showRegisterForm'])->name('register');
Route::post('/register', [AuthController::class, 'register']);
Route::get('/login', [AuthController::class, 'showLoginForm'])->name('login');
Route::post('/login', [AuthController::class, 'login']);//->middleware('auth')
Route::get('/dashboard', [AuthController::class, 'dashboard'])->name('dashboard');
Route::post('/logout', [AuthController::class, 'logout'])->name('logout');

Route::get('/home', [App\Http\Controllers\HomeController::class, 'index'])->name('home');
Route::get('/properties', [PropertiesController::class, 'index'])->name('properties');
Route::get('/properties/{id}', [PropertiesController::class, 'show'])->name('property.show');

Route::get('/payment', [StripeController::class, 'showPaymentForm'])->name('payment.form');
Route::post('/payment', [StripeController::class, 'handlePayment'])->name('payment.handle');

Route::get('/testpayment', [TestController::class, 'testPayment'])->name('test.payment');

Route::get('/deposit-funds', [HomeController::class, 'showDepositFunds'])->name('showDepositFunds');
