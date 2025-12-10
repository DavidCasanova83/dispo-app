<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * Simplifie la table agendas en supprimant les colonnes liées aux images
     * car l'image de couverture est maintenant globale (stockée à un chemin fixe)
     */
    public function up(): void
    {
        Schema::table('agendas', function (Blueprint $table) {
            $table->dropColumn([
                'name',           // Nom original fichier image
                'filename',       // Nom stocké image
                'path',           // Chemin image
                'thumbnail_path', // Chemin thumbnail
                'mime_type',      // Type MIME image
                'size',           // Taille image
                'width',          // Largeur image
                'height',         // Hauteur image
            ]);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('agendas', function (Blueprint $table) {
            $table->string('name')->after('id');
            $table->string('filename')->after('title');
            $table->string('path')->after('filename');
            $table->string('thumbnail_path')->nullable()->after('path');
            $table->string('mime_type')->nullable()->after('archived_at');
            $table->integer('size')->nullable()->after('mime_type');
            $table->integer('width')->nullable()->after('size');
            $table->integer('height')->nullable()->after('width');
        });
    }
};
