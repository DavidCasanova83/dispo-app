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
        Schema::table('brochure_clicks', function (Blueprint $table) {
            // Rendre image_id nullable pour permettre les clics sur l'agenda
            $table->foreignId('image_id')->nullable()->change();

            // Ajouter agenda_id (nullable)
            $table->foreignId('agenda_id')->nullable()->after('image_id')->constrained('agendas')->onDelete('cascade');

            // Index pour les requêtes sur agenda
            $table->index(['agenda_id', 'button_type']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('brochure_clicks', function (Blueprint $table) {
            $table->dropIndex(['agenda_id', 'button_type']);
            $table->dropForeign(['agenda_id']);
            $table->dropColumn('agenda_id');
            // Note: ne pas remettre image_id en non-nullable pour éviter la perte de données
        });
    }
};
