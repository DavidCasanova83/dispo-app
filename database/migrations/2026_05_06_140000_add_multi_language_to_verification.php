<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('verification_pages', function (Blueprint $table) {
            $table->string('url_en', 500)->nullable()->after('url');
            $table->string('url_it', 500)->nullable()->after('url_en');
        });

        Schema::table('verification_reviews', function (Blueprint $table) {
            $table->enum('language', ['fr', 'en', 'it'])->default('fr')->after('user_id');
            $table->index('language');
        });
    }

    public function down(): void
    {
        Schema::table('verification_pages', function (Blueprint $table) {
            $table->dropColumn(['url_en', 'url_it']);
        });

        Schema::table('verification_reviews', function (Blueprint $table) {
            $table->dropIndex(['language']);
            $table->dropColumn('language');
        });
    }
};
