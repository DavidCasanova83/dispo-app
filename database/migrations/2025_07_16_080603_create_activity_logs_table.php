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
        Schema::create('activity_logs', function (Blueprint $table) {
            $table->id();
            $table->string('event_type'); // 'status_change', 'data_sync', 'api_call', 'error'
            $table->string('action'); // 'activate', 'deactivate', 'sync_success', 'sync_error', etc.
            $table->string('entity_type')->nullable(); // 'accommodation', 'user', 'system'
            $table->string('entity_id')->nullable(); // ID of the related entity
            $table->json('data')->nullable(); // Additional data (old/new values, error details, etc.)
            $table->string('ip_address')->nullable(); // IP address for public actions
            $table->string('user_agent')->nullable(); // User agent for public actions
            $table->foreignId('user_id')->nullable()->constrained()->onDelete('set null'); // User who performed the action (if authenticated)
            $table->enum('status', ['success', 'error', 'warning', 'info'])->default('success');
            $table->text('message')->nullable(); // Human-readable message
            $table->timestamps();
            
            // Indexes for performance
            $table->index(['event_type', 'created_at']);
            $table->index(['entity_type', 'entity_id']);
            $table->index(['status', 'created_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('activity_logs');
    }
};
