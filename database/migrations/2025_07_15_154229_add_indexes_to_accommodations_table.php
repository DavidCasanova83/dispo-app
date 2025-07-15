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
        Schema::table('accommodations', function (Blueprint $table) {
            // Index pour les recherches textuelles sur le nom
            $table->index('name', 'idx_accommodations_name');
            
            // Index composites pour les filtres fréquents
            $table->index(['status', 'city'], 'idx_accommodations_status_city');
            $table->index(['type', 'status'], 'idx_accommodations_type_status');
            
            // Index pour les filtres de contact (recherche sur les champs nullables)
            $table->index('email', 'idx_accommodations_email');
            $table->index('phone', 'idx_accommodations_phone');
            $table->index('website', 'idx_accommodations_website');
            
            // Index composite pour les requêtes de statistiques complexes
            $table->index(['city', 'status', 'type'], 'idx_accommodations_stats');
            
            // Index pour les requêtes de tri par date de création
            $table->index('created_at', 'idx_accommodations_created_at');
            
            // Index pour les recherches par apidae_id (déjà unique mais améliore les performances)
            // Note: apidae_id a déjà un index unique, pas besoin d'en ajouter un autre
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('accommodations', function (Blueprint $table) {
            // Supprimer tous les index ajoutés
            $table->dropIndex('idx_accommodations_name');
            $table->dropIndex('idx_accommodations_status_city');
            $table->dropIndex('idx_accommodations_type_status');
            $table->dropIndex('idx_accommodations_email');
            $table->dropIndex('idx_accommodations_phone');
            $table->dropIndex('idx_accommodations_website');
            $table->dropIndex('idx_accommodations_stats');
            $table->dropIndex('idx_accommodations_created_at');
        });
    }
};
