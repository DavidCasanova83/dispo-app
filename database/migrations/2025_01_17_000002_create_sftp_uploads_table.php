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
        Schema::create('sftp_uploads', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade'); // Utilisateur qui a uploadé
            $table->foreignId('sftp_configuration_id')->constrained()->onDelete('cascade'); // Configuration utilisée
            $table->string('original_filename'); // Nom du fichier original
            $table->string('remote_filename'); // Nom du fichier sur le serveur SFTP
            $table->string('remote_path'); // Chemin complet sur le serveur
            $table->bigInteger('file_size'); // Taille du fichier en octets
            $table->string('status')->default('success'); // success, failed
            $table->text('error_message')->nullable(); // Message d'erreur si échec
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('sftp_uploads');
    }
};
