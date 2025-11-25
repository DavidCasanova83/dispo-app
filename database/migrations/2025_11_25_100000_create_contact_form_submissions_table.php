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
        Schema::create('contact_form_submissions', function (Blueprint $table) {
            $table->id();
            $table->unsignedInteger('form_id');

            // Visiteur
            $table->string('visiteur_nom');
            $table->string('visiteur_prenom');
            $table->string('visiteur_email');
            $table->string('visiteur_telephone', 50)->nullable();
            $table->text('visiteur_message');

            // Etablissement
            $table->string('etablissement_apidae_id', 50)->nullable();
            $table->string('etablissement_nom')->nullable();
            $table->string('etablissement_email')->nullable();

            // Metadata
            $table->string('url_page', 500)->nullable();
            $table->timestamp('date_soumission')->nullable();
            $table->string('ip_visiteur', 45)->nullable();
            $table->text('user_agent')->nullable();

            // Gestion
            $table->boolean('is_read')->default(false);
            $table->timestamps();

            // Index
            $table->index('form_id');
            $table->index('etablissement_apidae_id');
            $table->index('created_at');
            $table->index('is_read');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('contact_form_submissions');
    }
};
