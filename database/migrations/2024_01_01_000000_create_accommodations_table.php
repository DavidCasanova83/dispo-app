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
    Schema::create('accommodations', function (Blueprint $table) {
      $table->id();
      $table->string('apidae_id')->unique()->index();
      $table->string('name');
      $table->string('city')->nullable();
      $table->string('email')->nullable();
      $table->string('status')->default('pending');
      $table->timestamps();

      // Index pour améliorer les performances
      $table->index(['status', 'city']);
    });
  }

  /**
   * Reverse the migrations.
   */
  public function down(): void
  {
    Schema::dropIfExists('accommodations');
  }
};
