<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Corrections de traduction demandées à Claude (Suivi des traductions).
     *
     * Une ligne par demande : issue GitHub @claude dans le dépôt du site, puis
     * PR créée par le panel quand Claude a fini. Historique conservé : la ligne
     * reste si le verdict du traducteur est effacé (« Redemander une vérification »).
     * Statut en chaîne (pas d'enum) : ajouter un état ne demande pas de migration
     * propre à MySQL ou à SQLite.
     */
    public function up(): void
    {
        Schema::create('translation_claude_requests', function (Blueprint $table) {
            $table->id();
            $table->string('reference', 64)->unique();
            $table->foreignId('page_id')->constrained('verification_pages')->cascadeOnDelete();
            $table->foreignId('translation_check_id')->nullable()->constrained('translation_checks')->nullOnDelete();
            $table->string('language', 2);
            $table->string('status', 20);
            $table->text('translator_comment')->nullable();
            $table->text('message')->nullable();
            $table->unsignedInteger('issue_number')->nullable();
            $table->string('issue_url')->nullable();
            $table->string('branch')->nullable();
            $table->unsignedInteger('pr_number')->nullable();
            $table->string('pr_url')->nullable();
            $table->text('error')->nullable();
            $table->foreignId('requested_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('sent_at')->nullable();
            $table->timestamp('finished_at')->nullable();
            $table->timestamps();

            $table->index(['page_id', 'language']);
            $table->index('status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('translation_claude_requests');
    }
};
