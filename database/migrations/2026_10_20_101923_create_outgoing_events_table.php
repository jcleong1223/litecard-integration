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
        Schema::create('outgoing_events', function (Blueprint $table) {
            $table->string('id', 30)->primary();
            $table->unsignedBigInteger('api_client_id');
            $table->string('tenant_id', 50);
            $table->string('incoming_event_id', 30)->nullable();
            $table->foreign('incoming_event_id')
                ->references('id')
                ->on('incoming_events')
                ->nullOnDelete();
            $table->string('event_type', 30); // litecard.pass_created, litecard.voucher_updated
            $table->string('api_version', 20)->default('v1');
            $table->string('correlation_id', 100)->nullable(); // Correlate this result with the original request/workflow.
            $table->unsignedInteger('resource_version')->default(1);
            $table->string('aggregate_type', 64)->nullable();
            $table->string('aggregate_id', 100)->nullable();
            $table->string('operation', 50);
            $table->json('payload');
            $table->timestamp('occurred_at');
            $table->timestamps();

            $table->unique(['incoming_event_id', 'event_type']);
            $table->index(['api_client_id', 'aggregate_type', 'aggregate_id']);
            $table->index(['tenant_id', 'created_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('outgoing_events');
    }
};
