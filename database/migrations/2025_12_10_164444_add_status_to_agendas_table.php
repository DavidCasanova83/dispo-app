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

            // Supprimer l'index is_current si existant (compatible MySQL/MariaDB et SQLite)
            $this->dropIndexIfExists('agendas', 'agendas_is_current_index');

            // Supprimer la colonne is_current
            Schema::table('agendas', function (Blueprint $table) {
                $table->dropColumn('is_current');
            });
        }

        // Ajouter un index sur status si pas déjà existant
        if (!$this->indexExists('agendas', 'agendas_status_index')) {
            Schema::table('agendas', function (Blueprint $table) {
                $table->index('status');
            });
        }
    }

    /**
     * Vérifie si un index existe (compatible MySQL/MariaDB et SQLite)
     */
    private function indexExists(string $table, string $indexName): bool
    {
        $driver = Schema::getConnection()->getDriverName();

        if ($driver === 'sqlite') {
            $indexes = collect(DB::select("PRAGMA index_list('{$table}')"))->pluck('name')->toArray();
            return in_array($indexName, $indexes);
        }

        // MySQL/MariaDB
        $indexes = DB::select("SHOW INDEX FROM {$table} WHERE Key_name = ?", [$indexName]);
        return count($indexes) > 0;
    }

    /**
     * Supprime un index s'il existe (compatible MySQL/MariaDB et SQLite)
     */
    private function dropIndexIfExists(string $table, string $indexName): void
    {
        if (!$this->indexExists($table, $indexName)) {
            return;
        }

        $driver = Schema::getConnection()->getDriverName();

        if ($driver === 'sqlite') {
            DB::statement("DROP INDEX IF EXISTS {$indexName}");
        } else {
            // MySQL/MariaDB
            DB::statement("ALTER TABLE {$table} DROP INDEX {$indexName}");
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
