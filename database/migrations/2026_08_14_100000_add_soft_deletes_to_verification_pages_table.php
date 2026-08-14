<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * verification_reviews.page_id et verification_assignments.page_id sont en
 * ON DELETE CASCADE : supprimer une page détruisait aussi, définitivement,
 * toutes les relectures de vos collègues qui y étaient rattachées.
 *
 * Avec les SoftDeletes, aucun DELETE n'est émis : les cascades ne se déclenchent
 * pas et les relectures survivent à une suppression accidentelle. La restauration
 * se fait avec `php artisan verification:restore-page {id}`.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('verification_pages', function (Blueprint $table) {
            $table->softDeletes()->index();
        });
    }

    public function down(): void
    {
        Schema::table('verification_pages', function (Blueprint $table) {
            $table->dropSoftDeletes();
        });
    }
};
