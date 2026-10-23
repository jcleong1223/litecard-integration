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
        Schema::create('litecard_credentials', function (Blueprint $table) {
            $table->id();
            $table->foreignId('api_client_id')
                ->nullable()
                ->constrained('api_clients')
                ->nullOnDelete();
            $table->text('username_encrypted')->nullable();
            $table->text('password_encrypted')->nullable();
            $table->string('business_id', 64)->nullable();
            $table->string('status', 20)->default('unverified'); // unverified | verified | invalid | disabled
            $table->dateTime('last_pushed_at', 3)->nullable();
            $table->dateTime('last_verified_at', 3)->nullable();
            $table->dateTime('last_auth_failed_at', 3)->nullable();
            $table->string('last_auth_error', 255)->nullable();
            $table->text('access_token_encrypted')->nullable();
            $table->text('refresh_token_encrypted')->nullable();
            $table->dateTime('access_token_expires_at', 3)->nullable();
            $table->timestamps();

            $table->index('status');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('litecard_credentials');
    }
};
