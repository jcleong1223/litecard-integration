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
        Schema::create('incoming_event_deliveries', function (Blueprint $table) {
            $table->string('id', 32)->primary();
            $table->unsignedBigInteger('api_client_id');
            $table->string('tenant_id', 50);
            $table->string('incoming_event_id', 30);
            $table->foreign('incoming_event_id')->references('id')->on('incoming_events')->cascadeOnDelete();
            $table->string('operation', 50);
            $table->string('status', 30)->default('pending');
            $table->unsignedSmallInteger('attempt_count')->default(0);
            $table->timestamp('first_attempt_at')->nullable();
            $table->timestamp('last_attempt_at')->nullable();
            $table->timestamp('next_attempt_at')->nullable();
            $table->timestamp('delivered_at')->nullable();
            $table->timestamp('failed_at')->nullable();
            $table->unsignedSmallInteger('last_http_status')->nullable();
            $table->string('last_error_code', 50)->nullable();
            $table->text('last_error_message')->nullable();
            $table->unsignedInteger('last_response_duration_ms')->nullable();
            $table->text('response_body_excerpt')->nullable();
            $table->timestamps();

            $table->index(['status', 'next_attempt_at']);
            $table->index(['tenant_id', 'created_at']);
            $table->index('incoming_event_id');
            $table->index('delivered_at');
            $table->index('failed_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('incoming_event_deliveries');
    }
};
