<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('verification_reviews', function (Blueprint $table) {
            $table->text('content_comment')->nullable()->after('content_ok');
            $table->text('media_comment')->nullable()->after('media_ok');
        });
    }

    public function down(): void
    {
        Schema::table('verification_reviews', function (Blueprint $table) {
            $table->dropColumn(['content_comment', 'media_comment']);
        });
    }
};
