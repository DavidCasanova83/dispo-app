<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('verification_reviews', function (Blueprint $table) {
            $table->id();
            $table->foreignId('page_id')->constrained('verification_pages')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->boolean('content_ok');
            $table->boolean('media_ok');
            $table->text('remarks')->nullable();
            $table->text('admin_response')->nullable();
            $table->timestamp('admin_response_at')->nullable();
            $table->enum('status', ['pending_admin', 'in_progress', 'done', 'revision_requested'])->default('pending_admin');
            $table->timestamps();

            $table->index(['user_id', 'status']);
            $table->index('status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('verification_reviews');
    }
};
