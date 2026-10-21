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
        Schema::create('outgoing_event_deliveries', function (Blueprint $table) {
            $table->string('id', 32)->primary();
            $table->unsignedBigInteger('api_client_id');
            $table->string('outgoing_event_id', 30);
            $table->foreign('outgoing_event_id')->references('id')->on('outgoing_events')->cascadeOnDelete();
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
            $table->index(['outgoing_event_id', 'status']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('outgoing_event_deliveries');
    }
};
