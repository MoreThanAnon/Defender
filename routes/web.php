<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\PropertiesController;
use App\Http\Controllers\PropertyController;
use App\Http\Controllers\LoginController;
use App\Http\Controllers\StripeController;
use App\Http\Controllers\TestController;
use App\Http\Controllers\Auth\RegisterController;
use Illuminate\Foundation\Auth\EmailVerificationRequest;
use App\Http\Controllers\StripeWebhookController;

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

Route::get('/register', [RegisterController::class, 'showRegisterForm'])->name('register');
Route::post('/register', [RegisterController::class, 'register']);
Route::get('/login', [AuthController::class, 'showLoginForm'])->name('login');
Route::post('/login', [AuthController::class, 'login']);//->middleware('auth')
Route::get('/dashboard', [AuthController::class, 'dashboard'])->name('dashboard');
Route::post('/logout', [AuthController::class, 'logout'])->name('logout');
Route::get('/logout', [AuthController::class, 'logout'])->name('logout');
Route::get('/home', [HomeController::class, 'index'])->name('home');



Route::get('/properties', [PropertiesController::class, 'index'])->name('properties');
Route::get('/properties/{id}', [PropertiesController::class, 'show'])->name('property.show');
Route::get('/properties/{id}/edit', [PropertyController::class, 'edit'])->name('properties.edit');
Route::put('/properties/{id}', [PropertyController::class, 'update'])->name('properties.update');
Route::post('/properties/{id}/fund', [PropertyController::class, 'addToFund'])->middleware('auth');
Route::post('/properties/{id}/bid', [PropertyController::class, 'bid'])->name('properties.bid');

Route::get('/property/create', [PropertyController::class, 'create'])->name('property.create');
Route::post('/property/store', [PropertyController::class, 'store'])->name('property.store');

Route::get('/payment', [StripeController::class, 'showPaymentForm'])->name('payment.form');
Route::post('/payment', [StripeController::class, 'handlePayment'])->name('payment.handle');

Route::get('/testpayment', [TestController::class, 'testPayment'])->name('test.payment');

Route::get('/deposit-funds', [HomeController::class, 'showDepositFunds'])->name('showDepositFunds');

Route::get('/test-payment', [TestController::class, 'testPayment']);

// Add the route for email verification
Route::get('/email/verify/{id}/{hash}', function (EmailVerificationRequest $request) {
    $request->fulfill();
    return redirect('/home');
})->middleware(['auth', 'signed'])->name('verification.verify');


Route::get('/moderator', function () {
    return view('moderator');
})->name('moderator');

//Moderator routes

//Admin routes

Route::post('/stripe/webhook', [StripeWebhookController::class, 'handleWebhook']);