<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Vérification des traductions EN/IT par les traducteurs.
     *
     * Circuit indépendant de la vérification annuelle FR : un seul état par
     * page et par langue (contrainte unique), jamais purgé par la clôture annuelle.
     * Absence de ligne = traduction « à vérifier ».
     */
    public function up(): void
    {
        Schema::create('translation_checks', function (Blueprint $table) {
            $table->id();
            $table->foreignId('page_id')->constrained('verification_pages')->cascadeOnDelete();
            $table->enum('language', ['en', 'it']);
            $table->enum('status', ['correct', 'to_fix', 'in_progress', 'fixed']);
            $table->text('comment')->nullable();
            $table->foreignId('checked_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('checked_at')->nullable();
            $table->text('admin_response')->nullable();
            $table->foreignId('handled_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('handled_at')->nullable();
            $table->timestamps();

            $table->unique(['page_id', 'language']);
            $table->index('status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('translation_checks');
    }
};
