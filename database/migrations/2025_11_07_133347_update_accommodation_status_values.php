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
        // Mise à jour des valeurs de statut
        DB::table('accommodations')
            ->where('status', 'pending')
            ->update(['status' => 'en_attente']);

        DB::table('accommodations')
            ->where('status', 'active')
            ->update(['status' => 'disponible']);

        DB::table('accommodations')
            ->where('status', 'inactive')
            ->update(['status' => 'indisponible']);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Retour aux anciennes valeurs
        DB::table('accommodations')
            ->where('status', 'en_attente')
            ->update(['status' => 'pending']);

        DB::table('accommodations')
            ->where('status', 'disponible')
            ->update(['status' => 'active']);

        DB::table('accommodations')
            ->where('status', 'indisponible')
            ->update(['status' => 'inactive']);
    }
};
