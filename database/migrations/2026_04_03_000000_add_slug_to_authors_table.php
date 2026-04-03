<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('authors', function (Blueprint $table) {
            $table->string('slug')->unique()->nullable()->after('name');
        });

        // Générer les slugs pour les auteurs existants
        foreach (\App\Models\Author::all() as $author) {
            $author->slug = Str::slug($author->name);
            $author->save();
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('authors', function (Blueprint $table) {
            $table->dropColumn('slug');
        });
    }
};
