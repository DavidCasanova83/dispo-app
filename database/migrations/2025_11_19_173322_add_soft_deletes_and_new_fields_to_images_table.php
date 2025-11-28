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
        Schema::table('images', function (Blueprint $table) {
            $table->softDeletes();
            $table->string('alt_text')->nullable()->after('url');
            $table->text('description')->nullable()->after('alt_text');
            $table->integer('width')->nullable()->after('size');
            $table->integer('height')->nullable()->after('width');
            $table->string('thumbnail_path')->nullable()->after('path');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('images', function (Blueprint $table) {
            $table->dropSoftDeletes();
            $table->dropColumn(['alt_text', 'description', 'width', 'height', 'thumbnail_path']);
        });
    }
};
