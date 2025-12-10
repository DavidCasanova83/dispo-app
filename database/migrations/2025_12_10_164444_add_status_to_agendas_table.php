<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Vérifier si la colonne status existe déjà (cas de migration partielle)
        if (!Schema::hasColumn('agendas', 'status')) {
            Schema::table('agendas', function (Blueprint $table) {
                $table->enum('status', ['pending', 'current', 'archived'])
                    ->default('pending')
                    ->after('end_date');
            });
        }

        // Migrer les données existantes si is_current existe encore
        if (Schema::hasColumn('agendas', 'is_current')) {
            DB::table('agendas')
                ->where('is_current', true)
                ->update(['status' => 'current']);

            DB::table('agendas')
                ->where('is_current', false)
                ->whereNotNull('archived_at')
                ->update(['status' => 'archived']);

            // Pour SQLite, supprimer l'index avec SQL brut
            DB::statement('DROP INDEX IF EXISTS agendas_is_current_index');

            // Note: La suppression de la colonne is_current est problématique sur SQLite
            // Elle sera ignorée dans le code et reste dans la DB pour compatibilité
        }

        // Ajouter un index sur status si pas déjà existant
        $indexes = collect(DB::select("PRAGMA index_list('agendas')"))->pluck('name')->toArray();
        if (!in_array('agendas_status_index', $indexes)) {
            Schema::table('agendas', function (Blueprint $table) {
                $table->index('status');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Supprimer l'index sur status
        Schema::table('agendas', function (Blueprint $table) {
            $table->dropIndex(['status']);
        });

        // Supprimer le champ status
        Schema::table('agendas', function (Blueprint $table) {
            $table->dropColumn('status');
        });
    }
};
