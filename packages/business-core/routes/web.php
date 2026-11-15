<?php

use BusinessCore\Http\Controllers\AdminController;
use BusinessCore\Http\Controllers\BankController;
use BusinessCore\Http\Controllers\BusinessController;
use BusinessCore\Http\Controllers\DocumentController;
use BusinessCore\Http\Controllers\MemberController;
use BusinessCore\Http\Controllers\PaymentTaskController;
use BusinessCore\Http\Controllers\ProjectController;
use BusinessCore\Http\Controllers\ProposalController;
use BusinessCore\Http\Controllers\ReportController;
use BusinessCore\Http\Controllers\SupplierController;
use BusinessCore\Http\Controllers\TaskController;
use BusinessCore\Http\Middleware\ResolveBusiness;
use Illuminate\Support\Facades\Route;

Route::middleware(config('business-core.middleware', ['web', 'auth']))
    ->prefix(config('business-core.route_prefix', 'businesses'))
    ->name('bc.')
    ->group(function () {
        Route::get('/', [BusinessController::class, 'index'])->name('index');
        Route::get('/new', [BusinessController::class, 'create'])->name('create');
        Route::post('/', [BusinessController::class, 'store'])->name('store');

        Route::get('/invitations/{token}', [MemberController::class, 'showInvitation'])->name('invitations.show');
        Route::post('/invitations/{token}', [MemberController::class, 'acceptInvitation'])->name('invitations.accept');

        // Platform operator: every business, including those in storage.
        Route::prefix('admin')->name('admin.')->group(function () {
            Route::get('/', [AdminController::class, 'index'])->name('index');
            Route::post('/', [AdminController::class, 'store'])->name('store');
            Route::post('/{business}/director', [AdminController::class, 'assignDirector'])->name('director');
        });

        Route::prefix('{business}')->middleware(ResolveBusiness::class)->group(function () {
            Route::get('/', [BusinessController::class, 'show'])->name('show');
            Route::get('/settings', [BusinessController::class, 'edit'])->name('settings');
            Route::put('/settings', [BusinessController::class, 'update'])->name('settings.update');
            Route::post('/go-live', [BusinessController::class, 'goLive'])->name('go-live');
            Route::post('/status', [BusinessController::class, 'setStatus'])->name('status');

            Route::get('/people', [MemberController::class, 'index'])->name('members.index');
            Route::post('/people/invite', [MemberController::class, 'invite'])->name('members.invite');
            Route::put('/people/{membership}', [MemberController::class, 'update'])->name('members.update');
            Route::delete('/people/{membership}', [MemberController::class, 'destroy'])->name('members.destroy');
            Route::delete('/invites/{invitation}', [MemberController::class, 'revoke'])->name('members.revoke');

            Route::get('/bank', [BankController::class, 'index'])->name('bank.index');
            Route::post('/bank/import', [BankController::class, 'import'])->name('bank.import');
            Route::get('/bank/imports/{import}/file', [BankController::class, 'download'])->name('bank.download');
            Route::put('/bank/transactions/{transaction}', [BankController::class, 'update'])->name('bank.update');
            Route::post('/bank/transactions/{transaction}/match', [BankController::class, 'match'])->name('bank.match');
            Route::delete('/bank/transactions/{transaction}/match', [BankController::class, 'unmatch'])->name('bank.unmatch');

            Route::get('/projects', [ProjectController::class, 'index'])->name('projects.index');
            Route::post('/projects', [ProjectController::class, 'store'])->name('projects.store');
            Route::post('/assets', [ProjectController::class, 'storeAsset'])->name('assets.store');
            Route::get('/projects/{project}', [ProjectController::class, 'show'])->name('projects.show');
            Route::put('/projects/{project}', [ProjectController::class, 'update'])->name('projects.update');

            Route::get('/suppliers', [SupplierController::class, 'index'])->name('suppliers.index');
            Route::post('/suppliers', [SupplierController::class, 'store'])->name('suppliers.store');
            Route::put('/suppliers/{supplier}', [SupplierController::class, 'update'])->name('suppliers.update');

            Route::get('/proposals', [ProposalController::class, 'index'])->name('proposals.index');
            Route::post('/proposals', [ProposalController::class, 'store'])->name('proposals.store');
            Route::get('/proposals/{proposal}', [ProposalController::class, 'show'])->name('proposals.show');
            Route::post('/proposals/{proposal}/vote', [ProposalController::class, 'vote'])->name('proposals.vote');
            Route::post('/proposals/{proposal}/comments', [ProposalController::class, 'comment'])->name('proposals.comment');
            Route::post('/proposals/{proposal}/decision', [ProposalController::class, 'decide'])->name('proposals.decide');

            Route::get('/payments', [PaymentTaskController::class, 'index'])->name('payments.index');
            Route::post('/payments', [PaymentTaskController::class, 'store'])->name('payments.store');
            Route::post('/payments/{payment}/sent', [PaymentTaskController::class, 'markSent'])->name('payments.sent');
            Route::post('/payments/{payment}/cancel', [PaymentTaskController::class, 'cancel'])->name('payments.cancel');

            Route::get('/tasks', [TaskController::class, 'index'])->name('tasks.index');
            Route::post('/tasks', [TaskController::class, 'store'])->name('tasks.store');
            Route::put('/tasks/{task}', [TaskController::class, 'update'])->name('tasks.update');

            Route::get('/documents', [DocumentController::class, 'index'])->name('documents.index');
            Route::post('/documents', [DocumentController::class, 'store'])->name('documents.store');
            Route::get('/documents/{document}', [DocumentController::class, 'download'])->name('documents.download');

            Route::get('/reports', [ReportController::class, 'index'])->name('reports.index');
            Route::get('/reports/monthly/{period}', [ReportController::class, 'monthly'])->name('reports.monthly')->where('period', '\d{4}-\d{2}');
            Route::get('/reports/monthly/{period}/csv', [ReportController::class, 'monthlyCsv'])->name('reports.monthly.csv')->where('period', '\d{4}-\d{2}');
            Route::post('/reports/monthly/{period}/sign-off', [ReportController::class, 'signOff'])->name('reports.signoff')->where('period', '\d{4}-\d{2}');
            Route::get('/reports/audit', [ReportController::class, 'auditLog'])->name('reports.audit');
            Route::get('/reports/export', [ReportController::class, 'export'])->name('reports.export');
        });
    });
