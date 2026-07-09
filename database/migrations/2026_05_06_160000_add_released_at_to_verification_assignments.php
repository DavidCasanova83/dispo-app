<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('verification_assignments', function (Blueprint $table) {
            $table->timestamp('released_at')->nullable()->after('user_id');
            $table->index('released_at');
        });

        // Backfill : on considère que toutes les assignations existantes sont déjà
        // libérées, sinon les relecteurs verraient leur dashboard vidé brutalement
        // jusqu'au prochain dimanche.
        DB::table('verification_assignments')
            ->whereNull('released_at')
            ->update(['released_at' => DB::raw('created_at')]);
    }

    public function down(): void
    {
        Schema::table('verification_assignments', function (Blueprint $table) {
            $table->dropIndex(['released_at']);
            $table->dropColumn('released_at');
        });
    }
};
