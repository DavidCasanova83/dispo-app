<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('pdf_not_found_logs', function (Blueprint $table) {
            $table->id();
            $table->string('url', 2000);
            $table->string('referer', 2000)->nullable();
            $table->string('ip_address', 45)->nullable();
            $table->string('user_agent', 500)->nullable();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->unsignedInteger('hit_count')->default(1);
            $table->timestamp('last_hit_at');
            $table->timestamps();

            $table->index('url');
            $table->index('last_hit_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pdf_not_found_logs');
    }
};
