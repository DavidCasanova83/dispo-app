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
        Schema::create('agendas', function (Blueprint $table) {
            $table->id();
            $table->string('name');                    // Nom original du fichier
            $table->string('title')->nullable();       // Titre affiché
            $table->string('filename');                // Nom stocké
            $table->string('path');                    // Chemin image
            $table->string('thumbnail_path')->nullable();
            $table->string('pdf_path')->nullable();    // Chemin PDF
            $table->string('pdf_filename')->nullable(); // Nom original du PDF
            $table->text('description')->nullable();
            $table->date('start_date');                // Date début
            $table->date('end_date');                  // Date fin
            $table->boolean('is_current')->default(false); // Agenda en cours?
            $table->timestamp('archived_at')->nullable();  // Date d'archivage
            $table->string('mime_type')->nullable();
            $table->integer('size')->nullable();
            $table->integer('width')->nullable();
            $table->integer('height')->nullable();
            $table->foreignId('uploaded_by')->constrained('users')->onDelete('cascade');
            $table->timestamps();
            $table->softDeletes();

            $table->index('is_current');
            $table->index('start_date');
            $table->index('end_date');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('agendas');
    }
};
