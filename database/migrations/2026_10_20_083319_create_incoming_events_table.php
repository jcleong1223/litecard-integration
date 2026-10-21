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
        Schema::create('incoming_events', function (Blueprint $table) {
            $table->string('id', 30)->primary();
            $table->unsignedBigInteger('api_client_id');
            $table->string('tenant_id', 50);
            $table->string('tenant_code', 50)->nullable();
            $table->string('outlet_id', 50)->nullable();
            $table->string('outlet_name', 50)->nullable();
            $table->string('source_system', 50);
            $table->string('source_instance_id', 50);
            $table->string('source_event_id', 100);
            $table->string('source_request_id', 50);
            $table->string('event_type', 30);
            $table->string('api_version', 20)->default('v1');
            $table->string('aggregate_type', 30);
            $table->string('aggregate_id', 100);
            $table->unsignedInteger('resource_version')->default(1);
            $table->timestamp('occurred_at');
            $table->json('payload');
            $table->timestamps();

            $table->index(['api_client_id', 'created_at']);
            $table->index('event_type');
            $table->index(['aggregate_type', 'aggregate_id']);
            $table->index('source_event_id');

            $table->unique(
                ['source_system', 'source_instance_id', 'source_event_id', 'source_request_id'],
                'incoming_events_idempotency_unique'
            );
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('incoming_events');
    }
};
