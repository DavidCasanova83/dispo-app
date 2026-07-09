<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Dédoublonnage : pour chaque triplet (page_id, user_id, language), garder
        // uniquement la review avec le plus grand id (la plus récente).
        $duplicates = DB::table('verification_reviews')
            ->select('page_id', 'user_id', 'language')
            ->groupBy('page_id', 'user_id', 'language')
            ->havingRaw('COUNT(*) > 1')
            ->get();

        foreach ($duplicates as $row) {
            $keepId = DB::table('verification_reviews')
                ->where('page_id', $row->page_id)
                ->where('user_id', $row->user_id)
                ->where('language', $row->language)
                ->orderByDesc('id')
                ->value('id');

            DB::table('verification_reviews')
                ->where('page_id', $row->page_id)
                ->where('user_id', $row->user_id)
                ->where('language', $row->language)
                ->where('id', '!=', $keepId)
                ->delete();
        }

        Schema::table('verification_reviews', function (Blueprint $table) {
            $table->unique(['page_id', 'user_id', 'language'], 'reviews_page_user_lang_unique');
        });
    }

    public function down(): void
    {
        Schema::table('verification_reviews', function (Blueprint $table) {
            $table->dropUnique('reviews_page_user_lang_unique');
        });
    }
};
