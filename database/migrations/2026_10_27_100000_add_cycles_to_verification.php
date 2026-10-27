<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Cycles de vérification : la clôture annuelle archive les relectures au lieu
 * de les supprimer.
 *
 * - verification_pages.cycle : numéro du cycle en cours de la page ;
 * - verification_reviews.cycle : cycle auquel appartient la relecture ;
 * - verification_reviews.archived_at : renseigné à la clôture (relecture d'un
 *   cycle terminé, consultable, hors de tout calcul) ;
 * - verification_reviews.reviewer_seen_at : dernière lecture par le relecteur
 *   d'une réponse ou d'un traitement de l'admin (pastilles « nouveau ») ;
 * - verification_reviews.submitted_at : date de la dernière soumission du
 *   relecteur (updated_at bouge aussi quand l'admin traite le retour).
 *
 * L'unicité page + relecteur + langue devient page + relecteur + langue + cycle.
 * Le nouvel index est créé AVANT de retirer l'ancien : sous MySQL, l'ancien
 * peut porter la clé étrangère page_id, qui refuse de rester sans index.
 *
 * verification_reviews.user_id passe en ON DELETE SET NULL : un relecteur qui
 * supprimait son compte effaçait toutes ses relectures (ON DELETE CASCADE).
 *
 * Aucune ligne n'est supprimée. Les relectures existantes sont rattachées au
 * cycle 1, marquées « vues » pour ne pas déclencher de pastilles en masse, et
 * datées de leur création (meilleure approximation de leur soumission).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('verification_pages', function (Blueprint $table) {
            $table->unsignedInteger('cycle')->default(1)->after('status');
        });

        Schema::table('verification_reviews', function (Blueprint $table) {
            $table->unsignedInteger('cycle')->default(1)->after('language');
            $table->timestamp('archived_at')->nullable()->after('status')->index();
            $table->timestamp('reviewer_seen_at')->nullable()->after('archived_at');
            $table->timestamp('submitted_at')->nullable()->after('reviewer_seen_at');
        });

        DB::table('verification_reviews')->update([
            'reviewer_seen_at' => DB::raw('updated_at'),
            'submitted_at' => DB::raw('created_at'),
        ]);

        Schema::table('verification_reviews', function (Blueprint $table) {
            $table->unique(['page_id', 'user_id', 'language', 'cycle'], 'reviews_page_user_lang_cycle_unique');
        });

        Schema::table('verification_reviews', function (Blueprint $table) {
            $table->dropUnique('reviews_page_user_lang_unique');
        });

        Schema::table('verification_reviews', function (Blueprint $table) {
            $table->dropForeign(['user_id']);
        });

        Schema::table('verification_reviews', function (Blueprint $table) {
            $table->unsignedBigInteger('user_id')->nullable()->change();
            $table->foreign('user_id')->references('id')->on('users')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('verification_reviews', function (Blueprint $table) {
            $table->dropForeign(['user_id']);
        });

        Schema::table('verification_reviews', function (Blueprint $table) {
            $table->unsignedBigInteger('user_id')->nullable(false)->change();
            $table->foreign('user_id')->references('id')->on('users')->cascadeOnDelete();
        });

        Schema::table('verification_reviews', function (Blueprint $table) {
            $table->unique(['page_id', 'user_id', 'language'], 'reviews_page_user_lang_unique');
        });

        Schema::table('verification_reviews', function (Blueprint $table) {
            $table->dropUnique('reviews_page_user_lang_cycle_unique');
            $table->dropIndex(['archived_at']);
            $table->dropColumn(['cycle', 'archived_at', 'reviewer_seen_at', 'submitted_at']);
        });

        Schema::table('verification_pages', function (Blueprint $table) {
            $table->dropColumn('cycle');
        });
    }
};
