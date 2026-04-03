<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('sectors', function (Blueprint $table) {
            $table->string('slug')->unique()->after('name')->nullable();
        });

        foreach (\App\Models\Sector::all() as $sector) {
            $sector->slug = Str::slug($sector->name);
            $sector->save();
        }
    }

    public function down(): void
    {
        Schema::table('sectors', function (Blueprint $table) {
            $table->dropColumn('slug');
        });
    }
};
