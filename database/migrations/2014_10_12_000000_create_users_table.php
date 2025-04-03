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
        if (!Schema::hasTable('users')) {
            Schema::create('users', function (Blueprint $table) {
                $table->id();
                $table->string('username');
                $table->string('email')->unique();
                $table->timestamp('email_verified_at')->nullable();
                $table->string('password');
                $table->decimal('balance', 15, 2)->default(0.00);
                //Guest: Email not even confirmed
                //User: Email confirmed
                //Admin: Admin role
                //moderator: Moderator role
                $table->string('role')->default('guest');
                $table->rememberToken();
                $table->timestamps();
            });
        }

        Schema::create('bids', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users');
            $table->integer('property_d');
            $table->decimal('amount', 15, 2);
            $table->boolean('successful')->default(false);
            //Value: "failed" for money to go back to user and shows our attempt failed
            //pending: waiting for result
            //successful: money taken from user and bought property
            $table->string('status')->default('pending');
            $table->timestamps();
        });

    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('bids');
        Schema::dropIfExists('users');
    }
};
