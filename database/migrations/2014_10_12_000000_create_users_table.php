<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        //TODO: Before production, jesus christ do a security review
        //and make sure to move the address info to another table
        if (!Schema::hasTable('users')) {
            Schema::create('users', function (Blueprint $table) {
                $table->id();
                $table->string('username');
                $table->string('email')->unique();
                $table->timestamp('email_verified_at')->nullable();
                $table->string('password');
                $table->decimal('balance', 15, 2)->default(0.00);
                $table->string('stripe_customer_id')->nullable();
                $table->string('role')->default('guest');
                $table->rememberToken();
                $table->string('display_name')->nullable();
                $table->string('real_name')->nullable();
                $table->string('pm_last_four', 4)->nullable();
                $table->string('pm_type')->nullable();
                $table->string('eircode_region')->nullable();
                $table->timestamps();
            });
        }



    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('users');
    }
};
