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
use App\Http\Controllers\CustomerController;
use Illuminate\Support\Facades\Route;
use Illuminate\Http\Request;
use App\Http\Controllers\BillingController;
use App\Http\Controllers\BidController;

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


Route::post('/properties/{id}/bid', [PropertiesController::class, 'placeBid'])->name('properties.placeBid');
Route::post('/properties/{id}/bid/remove', [PropertiesController::class, 'removeBid'])->name('properties.removeBid');
Route::get('/properties', [PropertiesController::class, 'index'])->name('properties.index');
Route::get('/properties/{id}', [PropertyController::class, 'show'])->name('property.show');
Route::get('/properties/{id}/edit', [PropertyController::class, 'edit'])->name('properties.edit');
Route::put('/properties/{id}', [PropertyController::class, 'update'])->name('properties.update');
Route::post('/properties/{id}/fund', [PropertyController::class, 'addToFund'])->middleware('auth');

Route::get('/property/create', [PropertyController::class, 'create'])->name('property.create');
Route::post('/property/store', [PropertyController::class, 'store'])->name('property.store');

Route::get('/payment', [StripeController::class, 'showPaymentForm'])->name('payment.form');
Route::post('/payment', [StripeController::class, 'handlePayment'])->name('payment.handle');

Route::get('/testpayment', [TestController::class, 'testPayment'])->name('test.payment');


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

//Stripe logic, be very fucking careful with this
Route::post('/stripe/webhook', [StripeWebhookController::class, 'handleWebhook']);
Route::get('/deposit-funds', [StripeController::class, 'showDepositFunds'])->name('showDepositFunds');

Route::middleware('auth')->group(function () {
    Route::get('/customer/edit', [CustomerController::class, 'edit'])->name('customer.edit');
    Route::put('/customer/update', [CustomerController::class, 'update'])->name('customer.update');
});

Route::get('/customers', [CustomerController::class, 'index'])->name('customer.index');

Route::get('/api/eircode/{eircode}', function ($eircode) {
    // Simulate a call to the Python service
    $response = shell_exec(command: "python3 path/to/python_script.py {$eircode}");
    
    // Extract JSON from the response

});

Route::middleware(['auth'])->group(function () {
    Route::get('/billing', [StripeController::class, 'billing'])->name('billing');
    Route::post('/payment-method', [StripeController::class, 'storePaymentMethod'])->name('payment.method.store');
});

Route::middleware(['auth'])->group(function () {
    Route::get('/deposit-funds', [StripeController::class, 'showDepositFunds'])->name('deposit.funds');

    Route::post('/store-payment-method', [StripeController::class, 'storePaymentMethod'])
        ->name('stripe.storePaymentMethod');
});

Route::put('/bids/{bid}', [BidController::class, 'update'])->name('bids.update');