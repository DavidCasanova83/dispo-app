<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Corrections de page demandées à Claude depuis la fiche d'une page
 * (Vérification des pages), à partir des retours des relecteurs.
 *
 * Une ligne par demande : issue GitHub @claude dans le dépôt du site, puis PR
 * créée par le panel quand Claude a fini (VerificationClaudeService). Le texte
 * des retours envoyés est copié dans `feedback`, et leurs identifiants dans
 * `review_ids` : la demande reste lisible même après la clôture du cycle.
 * Statut en chaîne (pas d'enum), comme translation_claude_requests.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('verification_claude_requests', function (Blueprint $table) {
            $table->id();
            $table->string('reference', 64)->unique();
            $table->foreignId('page_id')->constrained('verification_pages')->cascadeOnDelete();
            $table->unsignedInteger('cycle')->default(1);
            $table->string('status', 20);
            $table->json('review_ids')->nullable();
            $table->text('feedback')->nullable();
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

            $table->index(['page_id', 'status']);
            $table->index('status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('verification_claude_requests');
    }
};
