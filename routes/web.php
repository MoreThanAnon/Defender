<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Http\Request;
use Illuminate\Foundation\Auth\EmailVerificationRequest;
use App\Http\Controllers\Auth\AuthController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\PropertiesController;
use App\Http\Controllers\PropertyController;
use App\Http\Controllers\StripeController;
use App\Http\Controllers\WithdrawalController;
use App\Http\Controllers\TestController;
use App\Http\Controllers\Auth\RegisterController;
use App\Http\Controllers\StripeWebhookController;
use App\Http\Controllers\CustomerController;
use App\Http\Controllers\BillingController;
use App\Http\Controllers\AdminController;

// Public Routes
Route::get('/', fn () => view('welcome'));
Route::get('/moderator', fn () => view('moderator'))->name('moderator');

// Authentication
Auth::routes();
Route::get('/register', [RegisterController::class, 'showRegisterForm'])->name('register');
Route::post('/register', [RegisterController::class, 'register']);
Route::get('/login', [AuthController::class, 'showLoginForm'])->name('login');
Route::post('/login', [AuthController::class, 'login']);
Route::match(['get', 'post'], '/logout', [AuthController::class, 'logout'])->name('logout');

// Dashboard & Home
Route::get('/dashboard', [AuthController::class, 'dashboard'])->name('dashboard');
Route::get('/home', [HomeController::class, 'index'])->name('home');

// Email Verification
Route::get('/email/verify/{id}/{hash}', function (EmailVerificationRequest $request) {
    $request->fulfill();
    return redirect('/home');
})->middleware(['auth', 'signed'])->name('verification.verify');

// Property Routes
Route::prefix('properties')->group(function () {
    Route::get('/', [PropertiesController::class, 'index'])->name('properties.index');
    Route::post('/{id}/fund', [PropertyController::class, 'addToFund'])->middleware('auth');
    Route::post('/{id}/set-fund-goal', [PropertiesController::class, 'setFundGoal'])->name('properties.setFundGoal');
    Route::post('/{id}/bid', [PropertiesController::class, 'placeBid'])->name('properties.placeBid');
    Route::post('/{id}/bid/remove', [PropertiesController::class, 'removeBid'])->name('properties.removeBid');
    Route::get('/set-status/{id}/{status}', [PropertyController::class, 'setStatus'])->name('properties.setStatus');
    Route::get('/{id}', [PropertyController::class, 'show'])->name('property.show');
    Route::get('/{id}/edit', [PropertyController::class, 'edit'])->name('properties.edit');
    Route::put('/{id}', [PropertyController::class, 'update'])->name('properties.update');

    //File uploads
    Route::get('/{id}/documents/upload', [PropertyController::class, 'uploadDocumentForm'])->name('properties.documents.upload');
    Route::post('/{id}/documents/upload', [PropertyController::class, 'storeDocument'])->name('properties.documents.store');

});

Route::get('/property/create', [PropertyController::class, 'create'])->name('property.create');
Route::post('/property/store', [PropertyController::class, 'store'])->name('property.store');

// Customer Routes
Route::middleware('auth')->group(function () {
    Route::post('/customer/edit', [CustomerController::class, 'edit'])->name('customer.edit');
    Route::get('/customer/edit', [CustomerController::class, 'edit'])->name('customer.edit');

    Route::put('/customer/update', [CustomerController::class, 'update'])->name('customer.update');
});

// Stripe Routes
Route::post('/stripe/webhook', [StripeWebhookController::class, 'handleWebhook']);
Route::get('/stripe/deposit', [StripeController::class, 'showDepositForm'])->name('stripe.deposit');

Route::middleware(['auth'])->group(function () {
    // Deposit
    Route::get('/deposit-funds', [StripeController::class, 'showDepositFunds'])->name('deposit.funds');
    Route::get('/deposit', [StripeController::class, 'showDepositForm'])->name('deposit.form');
    Route::post('/deposit/charge', [StripeController::class, 'processDeposit'])->name('deposit.charge');
    Route::post('/deposit/process', [StripeController::class, 'processDeposit'])->name('stripe.processDeposit');

    // Withdraw
    Route::get('/withdraw', [StripeController::class, 'showWithdrawForm'])->name('withdraw.form');
    Route::post('/withdraw', [WithdrawalController::class, 'process'])->name('stripe.withdraw');

    // Payment Flow
    Route::get('/payment/complete', [StripeController::class, 'paymentComplete'])->name('payment.complete');

    // Card Management
    Route::get('/card/add', [StripeController::class, 'showAddCardForm'])->name('card.add');
    Route::post('/card/save', [StripeController::class, 'storeCard'])->name('card.save');
    Route::post('/card/delete', [StripeController::class, 'deleteCard'])->name('card.delete');
    Route::get('/card/details', [StripeController::class, 'getCardDetails'])->name('card.details');
    Route::get('/stripe/create-setup-intent', [StripeController::class, 'createSetupIntent'])->name('stripe.createSetupIntent');
});

// Admin Routes
Route::middleware(['auth', 'role:admin'])->prefix('admin')->group(function () {
    Route::get('/dashboard', [AdminController::class, 'dashboard'])->name('admin.dashboard');
    Route::get('/properties', [AdminController::class, 'properties'])->name('admin.properties.index');
});

// Testing
if (app()->environment('local')) {
    Route::get('/testpayment', [TestController::class, 'testPayment'])->name('test.payment');
}
