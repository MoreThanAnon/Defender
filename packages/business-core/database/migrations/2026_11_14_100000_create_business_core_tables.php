<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $users = app(config('business-core.user_model'))->getTable();

        Schema::create('businesses', function (Blueprint $t) use ($users) {
            $t->id();
            $t->string('name');
            $t->string('slug')->unique();
            $t->string('type', 30)->default('business');
            // stored = sandbox / on the shelf, live = linked to a verified real company, archived = read-only
            $t->string('status', 20)->default('stored')->index();
            $t->string('legal_name')->nullable();
            $t->string('registration_number', 60)->nullable();
            $t->string('country', 2)->default('IE');
            $t->string('currency', 3)->default('EUR');
            $t->string('bank_name')->nullable();
            $t->string('bank_url')->nullable();
            $t->string('bank_account_label', 60)->nullable();
            $t->decimal('opening_balance', 14, 2)->nullable();
            $t->date('opening_balance_date')->nullable();
            $t->unsignedTinyInteger('quorum_percent')->default(50);
            $t->text('notes')->nullable();
            $t->foreignId('created_by')->nullable()->constrained($users)->nullOnDelete();
            $t->timestamp('went_live_at')->nullable();
            $t->foreignId('went_live_by')->nullable()->constrained($users)->nullOnDelete();
            $t->timestamps();
        });

        Schema::create('business_memberships', function (Blueprint $t) use ($users) {
            $t->id();
            $t->foreignId('business_id')->constrained('businesses')->cascadeOnDelete();
            $t->foreignId('user_id')->constrained($users)->cascadeOnDelete();
            $t->string('role', 30);
            $t->timestamps();
            $t->unique(['business_id', 'user_id']);
        });

        Schema::create('business_invitations', function (Blueprint $t) use ($users) {
            $t->id();
            $t->foreignId('business_id')->constrained('businesses')->cascadeOnDelete();
            $t->string('email');
            $t->string('role', 30);
            $t->string('token', 64)->unique();
            $t->foreignId('invited_by')->nullable()->constrained($users)->nullOnDelete();
            $t->timestamp('expires_at')->nullable();
            $t->timestamp('accepted_at')->nullable();
            $t->timestamps();
        });

        Schema::create('business_assets', function (Blueprint $t) {
            $t->id();
            $t->foreignId('business_id')->constrained('businesses')->cascadeOnDelete();
            $t->string('name');
            $t->string('kind', 30)->default('other');
            $t->string('location')->nullable();
            $t->text('notes')->nullable();
            $t->timestamps();
        });

        Schema::create('business_projects', function (Blueprint $t) {
            $t->id();
            $t->foreignId('business_id')->constrained('businesses')->cascadeOnDelete();
            $t->foreignId('asset_id')->nullable()->constrained('business_assets')->nullOnDelete();
            $t->string('name');
            $t->text('description')->nullable();
            $t->string('status', 20)->default('active');
            $t->decimal('budget_amount', 14, 2)->nullable();
            $t->date('due_date')->nullable();
            $t->timestamps();
        });

        Schema::create('business_suppliers', function (Blueprint $t) {
            $t->id();
            $t->foreignId('business_id')->constrained('businesses')->cascadeOnDelete();
            $t->string('name');
            $t->string('email')->nullable();
            $t->string('phone', 40)->nullable();
            $t->text('payment_details')->nullable();
            $t->text('notes')->nullable();
            $t->timestamps();
        });

        Schema::create('business_proposals', function (Blueprint $t) use ($users) {
            $t->id();
            $t->foreignId('business_id')->constrained('businesses')->cascadeOnDelete();
            $t->foreignId('project_id')->nullable()->constrained('business_projects')->nullOnDelete();
            $t->foreignId('supplier_id')->nullable()->constrained('business_suppliers')->nullOnDelete();
            $t->string('title');
            $t->text('body')->nullable();
            $t->decimal('amount', 14, 2)->nullable();
            $t->dateTime('closes_at')->nullable();
            $t->string('status', 20)->default('open')->index();
            $t->foreignId('created_by')->nullable()->constrained($users)->nullOnDelete();
            $t->foreignId('decided_by')->nullable()->constrained($users)->nullOnDelete();
            $t->timestamp('decided_at')->nullable();
            $t->text('decision_note')->nullable();
            $t->timestamps();
        });

        Schema::create('business_proposal_votes', function (Blueprint $t) use ($users) {
            $t->id();
            $t->foreignId('proposal_id')->constrained('business_proposals')->cascadeOnDelete();
            $t->foreignId('user_id')->constrained($users)->cascadeOnDelete();
            $t->string('choice', 10);
            $t->timestamps();
            $t->unique(['proposal_id', 'user_id']);
        });

        Schema::create('business_proposal_comments', function (Blueprint $t) use ($users) {
            $t->id();
            $t->foreignId('proposal_id')->constrained('business_proposals')->cascadeOnDelete();
            $t->foreignId('user_id')->nullable()->constrained($users)->nullOnDelete();
            $t->text('body');
            $t->timestamps();
        });

        Schema::create('business_payment_tasks', function (Blueprint $t) use ($users) {
            $t->id();
            $t->foreignId('business_id')->constrained('businesses')->cascadeOnDelete();
            $t->foreignId('project_id')->nullable()->constrained('business_projects')->nullOnDelete();
            $t->foreignId('proposal_id')->nullable()->constrained('business_proposals')->nullOnDelete();
            $t->foreignId('supplier_id')->nullable()->constrained('business_suppliers')->nullOnDelete();
            $t->string('payee_name');
            $t->string('reference', 100)->nullable();
            $t->decimal('amount', 14, 2);
            $t->date('due_date')->nullable();
            // prepared -> sent (user says they paid in their own bank) -> reconciled (bank statement evidence)
            $t->string('status', 20)->default('prepared')->index();
            $t->text('notes')->nullable();
            $t->foreignId('created_by')->nullable()->constrained($users)->nullOnDelete();
            $t->foreignId('sent_by')->nullable()->constrained($users)->nullOnDelete();
            $t->timestamp('sent_at')->nullable();
            $t->timestamp('reconciled_at')->nullable();
            $t->timestamps();
        });

        Schema::create('business_statement_imports', function (Blueprint $t) use ($users) {
            $t->id();
            $t->foreignId('business_id')->constrained('businesses')->cascadeOnDelete();
            $t->string('original_name');
            $t->string('path');
            $t->string('sha256', 64);
            $t->unsignedInteger('row_count')->default(0);
            $t->unsignedInteger('imported_count')->default(0);
            $t->unsignedInteger('duplicate_count')->default(0);
            $t->unsignedInteger('balance_warnings')->default(0);
            $t->date('period_start')->nullable();
            $t->date('period_end')->nullable();
            $t->decimal('closing_balance', 14, 2)->nullable();
            $t->foreignId('imported_by')->nullable()->constrained($users)->nullOnDelete();
            $t->timestamps();
            $t->unique(['business_id', 'sha256']);
        });

        Schema::create('business_bank_transactions', function (Blueprint $t) use ($users) {
            $t->id();
            $t->foreignId('business_id')->constrained('businesses')->cascadeOnDelete();
            $t->foreignId('statement_import_id')->constrained('business_statement_imports')->cascadeOnDelete();
            $t->unsignedInteger('row_number');
            // The bank's line exactly as uploaded. Never edited.
            $t->json('raw');
            $t->string('fingerprint', 64);
            $t->date('booked_on');
            $t->string('description', 500);
            $t->decimal('amount', 14, 2);
            $t->decimal('balance_after', 14, 2)->nullable();
            // Everything below is the customer's own annotation and may change.
            $t->string('category', 60)->nullable();
            $t->foreignId('project_id')->nullable()->constrained('business_projects')->nullOnDelete();
            $t->foreignId('payment_task_id')->nullable()->constrained('business_payment_tasks')->nullOnDelete();
            $t->string('status', 20)->default('unreviewed')->index();
            $t->string('note', 500)->nullable();
            $t->foreignId('reviewed_by')->nullable()->constrained($users)->nullOnDelete();
            $t->timestamp('reviewed_at')->nullable();
            $t->timestamps();
            $t->unique(['business_id', 'fingerprint']);
            $t->index(['business_id', 'booked_on']);
        });

        Schema::create('business_documents', function (Blueprint $t) use ($users) {
            $t->id();
            $t->foreignId('business_id')->constrained('businesses')->cascadeOnDelete();
            $t->string('title');
            $t->string('kind', 30)->default('other');
            $t->string('path');
            $t->string('original_name');
            $t->string('mime', 120)->nullable();
            $t->unsignedBigInteger('size')->default(0);
            $t->string('sha256', 64);
            $t->nullableMorphs('attached');
            $t->foreignId('uploaded_by')->nullable()->constrained($users)->nullOnDelete();
            $t->timestamps();
        });

        Schema::create('business_tasks', function (Blueprint $t) use ($users) {
            $t->id();
            $t->foreignId('business_id')->constrained('businesses')->cascadeOnDelete();
            $t->foreignId('project_id')->nullable()->constrained('business_projects')->nullOnDelete();
            $t->string('title');
            $t->text('notes')->nullable();
            $t->foreignId('assigned_to')->nullable()->constrained($users)->nullOnDelete();
            $t->date('due_date')->nullable();
            $t->string('status', 20)->default('todo')->index();
            $t->timestamp('completed_at')->nullable();
            $t->foreignId('created_by')->nullable()->constrained($users)->nullOnDelete();
            $t->timestamps();
        });

        Schema::create('business_report_signoffs', function (Blueprint $t) use ($users) {
            $t->id();
            $t->foreignId('business_id')->constrained('businesses')->cascadeOnDelete();
            $t->char('period', 7);
            $t->foreignId('signed_by')->nullable()->constrained($users)->nullOnDelete();
            $t->string('note', 500)->nullable();
            $t->timestamp('signed_at');
            $t->unique(['business_id', 'period']);
        });

        // Append-only. No updated_at, and the model refuses updates and deletes.
        Schema::create('business_audit_events', function (Blueprint $t) use ($users) {
            $t->id();
            $t->foreignId('business_id')->constrained('businesses')->cascadeOnDelete();
            $t->foreignId('user_id')->nullable()->constrained($users)->nullOnDelete();
            $t->string('action', 80)->index();
            $t->nullableMorphs('subject');
            $t->json('meta')->nullable();
            $t->string('ip', 45)->nullable();
            $t->timestamp('created_at')->useCurrent();
        });
    }

    public function down(): void
    {
        foreach ([
            'business_audit_events', 'business_report_signoffs', 'business_tasks', 'business_documents',
            'business_bank_transactions', 'business_statement_imports', 'business_payment_tasks',
            'business_proposal_comments', 'business_proposal_votes', 'business_proposals', 'business_suppliers',
            'business_projects', 'business_assets', 'business_invitations', 'business_memberships', 'businesses',
        ] as $table) {
            Schema::dropIfExists($table);
        }
    }
};
