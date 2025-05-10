<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Http\Request;
use Illuminate\Foundation\Auth\EmailVerificationRequest;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\PropertiesController;
use App\Http\Controllers\PropertyController;
use App\Http\Controllers\LoginController;
use App\Http\Controllers\StripeController;
use App\Http\Controllers\TestController;
use App\Http\Controllers\Auth\RegisterController;
use App\Http\Controllers\StripeWebhookController;
use App\Http\Controllers\CustomerController;
use App\Http\Controllers\BillingController;
use App\Http\Controllers\BidsController;

Route::get('/', function () {
    return view('welcome');
});

Auth::routes();

// Auth Routes
Route::get('/register', [RegisterController::class, 'showRegisterForm'])->name('register');
Route::post('/register', [RegisterController::class, 'register']);
Route::get('/login', [AuthController::class, 'showLoginForm'])->name('login');
Route::post('/login', [AuthController::class, 'login']);
Route::get('/dashboard', [AuthController::class, 'dashboard'])->name('dashboard');
Route::match(['get', 'post'], '/logout', [AuthController::class, 'logout'])->name('logout');

// Home
Route::get('/home', [HomeController::class, 'index'])->name('home');

// Property Routes
Route::get('/properties', [PropertiesController::class, 'index'])->name('properties.index');
Route::get('/properties/{id}', [PropertyController::class, 'show'])->name('property.show');
Route::get('/properties/{id}/edit', [PropertyController::class, 'edit'])->name('properties.edit');
Route::put('/properties/{id}', [PropertyController::class, 'update'])->name('properties.update');
Route::get('/properties/set-status/{id}/{status}', [PropertyController::class, 'setStatus'])->name('properties.setStatus');
Route::post('/properties/{id}/fund', [PropertyController::class, 'addToFund'])->middleware('auth');
Route::post('/properties/{id}/set-fund-goal', [PropertiesController::class, 'setFundGoal'])->name('properties.setFundGoal');
Route::get('/property/create', [PropertyController::class, 'create'])->name('property.create');
Route::post('/property/store', [PropertyController::class, 'store'])->name('property.store');

// Email Verification
Route::get('/email/verify/{id}/{hash}', function (EmailVerificationRequest $request) {
    $request->fulfill();
    return redirect('/home');
})->middleware(['auth', 'signed'])->name('verification.verify');

// Moderator
Route::get('/moderator', fn () => view('moderator'))->name('moderator');

// Test
Route::get('/testpayment', [TestController::class, 'testPayment'])->name('test.payment');

// Stripe Webhook
Route::post('/stripe/webhook', [StripeWebhookController::class, 'handleWebhook']);

// Stripe Deposit & Billing
Route::middleware(['auth'])->group(function () {
    Route::get('/billing', [StripeController::class, 'billing'])->name('billing');
    Route::post('/payment-method', [StripeController::class, 'storePaymentMethod'])->name('payment.method.store');
    Route::post('/store-payment-method', [StripeController::class, 'storePaymentMethod'])->name('stripe.storePaymentMethod');

    Route::get('/deposit-funds', [StripeController::class, 'showDepositFunds'])->name('deposit.funds');

    Route::get('/deposit', [StripeController::class, 'showDepositForm'])->name('deposit.form');
    Route::post('/deposit/charge', [StripeController::class, 'processDeposit'])->name('deposit.charge');

    Route::post('/deposit/process', [StripeController::class, 'processDeposit'])->name('stripe.processDeposit');
    Route::post('/create-payment-intent', [StripeController::class, 'createPaymentIntent'])->name('stripe.createPaymentIntent');

    Route::get('/payment/complete', [StripeController::class, 'paymentComplete'])->name('payment.complete');
    Route::get('/payment-complete', [StripeController::class, 'paymentComplete'])->name('payment.complete');

    // Card management
    Route::get('/card/add', [StripeController::class, 'showAddCardForm'])->name('card.add');
    Route::post('/card/save', [StripeController::class, 'storeCard'])->name('card.save');
    Route::post('/card/delete', [StripeController::class, 'deleteCard'])->name('card.delete');
    Route::get('/card/details', [StripeController::class, 'getCardDetails'])->name('card.details');

    Route::get('/stripe/create-setup-intent', [StripeController::class, 'createSetupIntent'])->name('stripe.createSetupIntent');
});

// Customer Routes
Route::middleware('auth')->group(function () {
    Route::get('/customer/edit', [CustomerController::class, 'edit'])->name('customer.edit');
    Route::put('/customer/update', [CustomerController::class, 'update'])->name('customer.update');
});

Route::get('/customers', [CustomerController::class, 'index'])->name('customer.index');

// Bids
Route::post('/properties/{id}/bid', [PropertiesController::class, 'placeBid'])->name('properties.placeBid');
Route::post('/properties/{id}/bid/remove', [PropertiesController::class, 'removeBid'])->name('properties.removeBid');
Route::put('/bids/{bid}', [BidsController::class, 'update'])->name('bids.update');
Route::get('/stripe/deposit', [StripeController::class, 'showDepositForm'])->name('stripe.deposit');
// Eircode API Stub
Route::get('/api/eircode/{eircode}', function ($eircode) {
    $response = shell_exec("python3 path/to/python_script.py {$eircode}");
});