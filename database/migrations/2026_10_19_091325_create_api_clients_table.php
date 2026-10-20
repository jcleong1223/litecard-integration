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
        Schema::create('api_clients', function (Blueprint $table) {
            $table->id();
            $table->string('name', 100);
            $table->string('client_id', 50)->unique();
            $table->string('identifier', 35)->unique();
            $table->json('allowed_ips')->nullable();
            $table->string('status', 20)->default('active');
            $table->timestamp('last_used_at')->nullable();
            $table->string('callback_url');
            $table->text('litecard_username_encrypted');
            $table->text('litecard_password_encrypted');
            $table->text('litecard_token_encrypted');
            $table->string('litecard_status')->default('valid')->comment('valid, invalid');
            $table->timestamp('litecard_last_used_at')->nullable();
            $table->timestamp('litecard_token_expires_at')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index('status');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('api_clients');
    }
};
