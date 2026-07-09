<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('verification_pages', function (Blueprint $table) {
            $table->timestamp('validated_at')->nullable()->after('status');
        });

        DB::statement("ALTER TABLE verification_pages MODIFY COLUMN status ENUM('pending', 'in_progress', 'awaiting_validation', 'validated', 'needs_fix') NOT NULL DEFAULT 'pending'");

        // Backfill : pour les pages déjà validées avant l'introduction de validated_at,
        // on prend la dernière review FR 'done' comme date de validation (proxy de l'ancienne logique
        // du cron qui regardait l'âge de la dernière review). Fallback sur updated_at de la page
        // si aucune review FR done n'existe (peu probable mais on sécurise).
        DB::statement("
            UPDATE verification_pages vp
            LEFT JOIN (
                SELECT page_id, MAX(updated_at) AS last_done_at
                FROM verification_reviews
                WHERE language = 'fr' AND status = 'done'
                GROUP BY page_id
            ) vr ON vr.page_id = vp.id
            SET vp.validated_at = COALESCE(vr.last_done_at, vp.updated_at)
            WHERE vp.status = 'validated'
        ");
    }

    public function down(): void
    {
        // Sécurise le rollback : ramène toute page en 'awaiting_validation' à 'in_progress'
        // avant de retirer la valeur de l'enum.
        DB::table('verification_pages')
            ->where('status', 'awaiting_validation')
            ->update(['status' => 'in_progress']);

        DB::statement("ALTER TABLE verification_pages MODIFY COLUMN status ENUM('pending', 'in_progress', 'validated', 'needs_fix') NOT NULL DEFAULT 'pending'");

        Schema::table('verification_pages', function (Blueprint $table) {
            $table->dropColumn('validated_at');
        });
    }
};
