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
            $table->enum('category', ['decouvrir', 'sejourner', 'activites', 'agenda', 'infos_pratiques'])
                ->nullable()
                ->after('theme');
            $table->timestamp('last_seen_in_sitemap_at')->nullable()->after('deadline');
            $table->boolean('is_in_sitemap')->default(false)->after('last_seen_in_sitemap_at');

            $table->index('category');
            $table->index('is_in_sitemap');
        });

        // Dédoublonnage avant l'index unique : on garde la plus ancienne ligne par URL.
        // (Sécurise le cas où il y aurait déjà des doublons en BDD.)
        $duplicateUrls = DB::table('verification_pages')
            ->select('url')
            ->groupBy('url')
            ->havingRaw('COUNT(*) > 1')
            ->pluck('url');

        foreach ($duplicateUrls as $url) {
            $keepId = DB::table('verification_pages')->where('url', $url)->orderBy('id')->value('id');
            DB::table('verification_pages')->where('url', $url)->where('id', '!=', $keepId)->delete();
        }

        Schema::table('verification_pages', function (Blueprint $table) {
            $table->unique('url');
        });
    }

    public function down(): void
    {
        Schema::table('verification_pages', function (Blueprint $table) {
            $table->dropUnique(['url']);
            $table->dropIndex(['category']);
            $table->dropIndex(['is_in_sitemap']);
            $table->dropColumn(['category', 'last_seen_in_sitemap_at', 'is_in_sitemap']);
        });
    }
};
