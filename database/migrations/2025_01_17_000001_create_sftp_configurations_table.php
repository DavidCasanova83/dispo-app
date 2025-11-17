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
        Schema::create('sftp_configurations', function (Blueprint $table) {
            $table->id();
            $table->string('name')->default('Configuration SFTP'); // Nom de la configuration
            $table->string('host'); // Adresse du serveur SFTP
            $table->integer('port')->default(22); // Port SFTP (par défaut 22)
            $table->string('username'); // Nom d'utilisateur SFTP
            $table->text('password')->nullable(); // Mot de passe (crypté)
            $table->text('private_key')->nullable(); // Clé privée SSH (optionnelle)
            $table->string('remote_path')->default('/'); // Dossier de destination sur le serveur
            $table->integer('timeout')->default(30); // Timeout de connexion en secondes
            $table->boolean('is_active')->default(true); // Configuration active ou non
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('sftp_configurations');
    }
};
