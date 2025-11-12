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
        Schema::create('qualifications', function (Blueprint $table) {
            $table->id();
            $table->enum('city', [
                'annot',
                'colmars-les-alpes',
                'entrevaux',
                'la-palud-sur-verdon',
                'saint-andre-les-alpes'
            ])->index();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->integer('current_step')->default(1);
            $table->json('form_data')->nullable();
            $table->boolean('completed')->default(false);
            $table->timestamp('completed_at')->nullable();
            $table->timestamps();

            // Index composé pour améliorer les performances de recherche
            $table->index(['city', 'completed']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('qualifications');
    }
};
