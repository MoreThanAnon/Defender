<?php

use App\Http\Controllers\AdminController;
use App\Http\Controllers\Auth\AuthController;
use App\Http\Controllers\Auth\ConfirmPasswordController;
use App\Http\Controllers\Auth\ForgotPasswordController;
use App\Http\Controllers\Auth\RegisterController;
use App\Http\Controllers\Auth\ResetPasswordController;
use App\Http\Controllers\CustomerController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\PropertiesController;
use App\Http\Controllers\PropertyController;
use App\Http\Controllers\ProposalController;
use App\Http\Controllers\StripeController;
use App\Http\Controllers\StripeWebhookController;
use App\Http\Controllers\TestRunnerController;
use App\Http\Controllers\TransactionController;
use App\Http\Controllers\UpdateController;
use App\Http\Controllers\WithdrawalController;
use App\Http\Controllers\DecidimController;
use App\Http\Controllers\DecidimOAuthController;
use Illuminate\Foundation\Auth\EmailVerificationRequest;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::get('/', fn () => view('welcome'));
Route::view('/moderator', 'moderator')->name('moderator');
Route::get('/decidim/test', [DecidimController::class, 'test'])->name('decidim.test');
Route::get('/oauth/authorize', [DecidimOAuthController::class, 'oauthAuthorize'])
    ->middleware('auth')
    ->name('decidim.oauth.authorize');

Route::middleware('guest')->group(function () {
    Route::get('/register', [RegisterController::class, 'showRegisterForm'])->name('register');
    Route::post('/register', [RegisterController::class, 'register']);
    Route::get('/login', [AuthController::class, 'showLoginForm'])->name('login');
    Route::post('/login', [AuthController::class, 'login']);

    Route::get('/password/reset', [ForgotPasswordController::class, 'showLinkRequestForm'])->name('password.request');
    Route::post('/password/email', [ForgotPasswordController::class, 'sendResetLinkEmail'])->name('password.email');
    Route::get('/password/reset/{token}', [ResetPasswordController::class, 'showResetForm'])->name('password.reset');
});

Route::post('/password/reset', [ResetPasswordController::class, 'reset'])->name('password.update');

Route::middleware('auth')->group(function () {
    Route::get('/governance', fn () => redirect()->away(rtrim(config('decidim.base_url'), '/').'/sso/laravel'))
        ->name('governance');
    Route::post('/logout', [AuthController::class, 'logout'])->name('logout');
    Route::get('/dashboard', [AuthController::class, 'dashboard'])->name('dashboard');
    Route::get('/home', [HomeController::class, 'index'])->name('home');
    Route::post('/decidim/sync-offering/{offering}', [DecidimController::class, 'syncOffering'])->name('decidim.sync');

    Route::get('/email/verify', fn () => view('auth.verify'))->name('verification.notice');
    Route::post('/email/verification-notification', function (Request $request) {
        if (! $request->user()->hasVerifiedEmail()) {
            $request->user()->sendEmailVerificationNotification();
        }

        return back()->with('resent', true);
    })->middleware('throttle:6,1')->name('verification.resend');

    Route::get('/password/confirm', [ConfirmPasswordController::class, 'showConfirmForm'])->name('password.confirm');
    Route::post('/password/confirm', [ConfirmPasswordController::class, 'confirm']);

    Route::match(['get', 'post'], '/customer/edit', [CustomerController::class, 'edit'])->name('customer.edit');

    Route::get('/stripe/deposit', [StripeController::class, 'showDepositForm'])->name('stripe.deposit');
    Route::get('/deposit', [StripeController::class, 'showDepositForm'])->name('deposit.form');
    Route::post('/deposit/charge', [StripeController::class, 'processDeposit'])->name('deposit.charge');
    Route::post('/deposit/process', [StripeController::class, 'processDeposit'])->name('stripe.processDeposit');
    Route::get('/withdraw', [StripeController::class, 'showWithdrawForm'])->name('withdraw.form');
    Route::post('/withdraw', [WithdrawalController::class, 'process'])->name('stripe.withdraw');
    Route::get('/payment/complete', [StripeController::class, 'paymentComplete'])->name('payment.complete');
    Route::get('/card/add', [StripeController::class, 'showAddCardForm'])->name('card.add');
    Route::post('/card/save', [StripeController::class, 'storeCard'])->name('card.save');
    Route::post('/card/delete', [StripeController::class, 'deleteCard'])->name('card.delete');
    Route::get('/card/details', [StripeController::class, 'getCardDetails'])->name('card.details');
    Route::get('/stripe/create-setup-intent', [StripeController::class, 'createSetupIntent'])->name('stripe.createSetupIntent');
    Route::get('/transactions', [TransactionController::class, 'index'])->name('transactions.index');
});

Route::get('/email/verify/{id}/{hash}', function (EmailVerificationRequest $request) {
    $request->fulfill();

    return redirect()->route('home');
})->middleware(['auth', 'signed'])->name('verification.verify');

Route::prefix('properties')->group(function () {
    Route::get('/', [PropertiesController::class, 'index'])->name('properties.index');
    Route::post('/{id}/bid', [PropertiesController::class, 'placeBid'])->middleware('auth')->name('properties.placeBid');
    Route::post('/{id}/bid/remove', [PropertiesController::class, 'removeBid'])->middleware('auth')->name('properties.removeBid');
    Route::get('/{id}/funding-status', [PropertyController::class, 'fundingStatus'])->name('properties.fundingStatus');
    Route::get('/{id}', [PropertyController::class, 'show'])->name('property.show');

    Route::get('/{property}/proposals', [ProposalController::class, 'index'])->name('proposals.index');
    Route::get('/{property}/proposals/create', [ProposalController::class, 'create'])->name('proposals.create');
    Route::post('/{property}/proposals', [ProposalController::class, 'store'])->name('proposals.store');

    //Route::middleware(['auth', 'role:admin,moderator'])->group(function () {
        Route::get('/property/create', [PropertyController::class, 'create'])->name('property.create');
        Route::post('/property/store', [PropertyController::class, 'store'])->name('property.store');
        Route::post('/set-status/{id}/{status}', [PropertyController::class, 'setStatus'])->name('properties.setStatus');
        Route::get('/{id}/edit', [PropertyController::class, 'edit'])->name('properties.edit');
        Route::put('/{id}', [PropertyController::class, 'update'])->name('properties.update');
        Route::get('/{property}/updates/create', [UpdateController::class, 'create'])->name('updates.create');
        Route::post('/updates', [UpdateController::class, 'store'])->name('updates.store');
   // });
});

Route::middleware(['auth', 'role:admin,moderator'])->group(function () {
    Route::prefix('admin')->group(function () {
        Route::get('/users', [AdminController::class, 'users'])->name('admin.users.index');
        Route::post('/act-as', [AdminController::class, 'impersonate'])->name('admin.impersonate');
        Route::post('/stop-impersonating', [AdminController::class, 'stopImpersonating'])->name('admin.impersonate.stop');
    });
});

Route::get('/proposals/{proposal}', [ProposalController::class, 'show'])->name('proposals.show');
Route::get('/proposals/{proposal}/edit', [ProposalController::class, 'edit'])->name('proposals.edit');
Route::put('/proposals/{proposal}', [ProposalController::class, 'update'])->name('proposals.update');
Route::delete('/proposals/{proposal}', [ProposalController::class, 'destroy'])->name('proposals.destroy');
Route::post('/proposals/{proposal}/vote', [ProposalController::class, 'vote'])->name('proposals.vote');

Route::post('/stripe/webhook', [StripeWebhookController::class, 'handleWebhook']);

Route::prefix('test-runner')->name('test-runner.')->group(function () {
    Route::get('/', [TestRunnerController::class, 'index'])->name('index');
    Route::post('/purchase-simulation', [TestRunnerController::class, 'startPurchaseSimulation'])->name('purchase-simulation.start');
    Route::get('/purchase-simulation/{runId}', [TestRunnerController::class, 'purchaseSimulationStatus'])->name('purchase-simulation.status');
});

Route::middleware(['auth', 'role:admin'])->prefix('admin')->group(function () {
    Route::get('/dashboard', [AdminController::class, 'dashboard'])->name('admin.dashboard');
    Route::get('/properties', [AdminController::class, 'properties'])->name('admin.properties.index');
    Route::get('/decidim', [AdminController::class, 'decidim'])->name('admin.decidim.index');
    Route::post('/decidim/offerings/{offering}/retry', [AdminController::class, 'retryDecidim'])->name('admin.decidim.retry');
});
