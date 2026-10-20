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
        Schema::create('api_client_secrets', function (Blueprint $table) {
            $table->id();
            $table->foreignId('api_client_id')
                ->nullable()
                ->constrained('api_clients')
                ->nullOnDelete();
            $table->string('direction')->default('inbound')->comment('inbound or callback');
            $table->string('key_id')->nullable();
            $table->text('secret_encrypted');
            $table->string('status', 20)->default('active')->comment('active, retiring, revoked');
            $table->timestamp('expires_at')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('api_client_secrets');
    }
};
