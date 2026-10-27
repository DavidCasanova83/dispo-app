<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Journal de la vérification des pages : tout ce qui arrive à une page
 * (relecture rendue, version précédente d'une relecture renvoyée, traitement
 * et réponse de l'admin, assignation, distribution, clôture, renouvellement,
 * retrait du suivi, restauration). Rien n'y est jamais modifié ni supprimé par
 * l'application ; les noms utiles sont copiés dans `data` pour rester lisibles
 * si un compte est supprimé.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('verification_events', function (Blueprint $table) {
            $table->id();
            $table->foreignId('page_id')->constrained('verification_pages')->cascadeOnDelete();
            $table->unsignedInteger('cycle')->default(1);
            $table->string('type', 40);
            $table->foreignId('review_id')->nullable()->constrained('verification_reviews')->nullOnDelete();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('actor_id')->nullable()->constrained('users')->nullOnDelete();
            $table->json('data')->nullable();
            $table->timestamp('created_at')->nullable();

            $table->index(['page_id', 'created_at']);
            $table->index('type');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('verification_events');
    }
};
