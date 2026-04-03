<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('categories', function (Blueprint $table) {
            $table->string('slug')->unique()->after('name')->nullable();
        });

        Schema::table('sub_categories', function (Blueprint $table) {
            $table->string('slug')->after('name')->nullable();
        });

        // Générer les slugs pour les catégories existantes
        foreach (\App\Models\Category::all() as $category) {
            $category->update(['slug' => Str::slug($category->name)]);
        }

        // Générer les slugs pour les sous-catégories existantes
        foreach (\App\Models\SubCategory::all() as $subCategory) {
            $subCategory->update(['slug' => Str::slug($subCategory->name)]);
        }
    }

    public function down(): void
    {
        Schema::table('categories', function (Blueprint $table) {
            $table->dropColumn('slug');
        });

        Schema::table('sub_categories', function (Blueprint $table) {
            $table->dropColumn('slug');
        });
    }
};
