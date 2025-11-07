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
        Schema::table('accommodations', function (Blueprint $table) {
            $table->timestamp('email_sent_at')->nullable()->after('status');
            $table->string('email_response_token', 64)->nullable()->unique()->after('email_sent_at');
            $table->timestamp('last_response_at')->nullable()->after('email_response_token');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('accommodations', function (Blueprint $table) {
            $table->dropColumn(['email_sent_at', 'email_response_token', 'last_response_at']);
        });
    }
};
