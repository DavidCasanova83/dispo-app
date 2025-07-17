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
        Schema::create('user_color_settings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->string('primary_color', 7)->default('#3A9C92');
            $table->string('secondary_color', 7)->default('#7AB6A8');
            $table->string('accent_color', 7)->default('#FFFDF4');
            $table->string('background_color', 7)->default('#FAF7F3');
            $table->timestamps();
            
            $table->unique('user_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('user_color_settings');
    }
};
