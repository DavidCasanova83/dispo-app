<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

/**
 * Versions linguistiques des brochures.
 *
 * - language        : langue du document (fr pour les brochures, en / it pour leurs versions) ;
 * - version_of_id   : brochure française dont ce document est la version EN ou IT ;
 * - extra_languages : autres langues contenues dans le même document (carte bilingue FR/EN) ;
 * - slug            : adresse permanente /brochure/{slug}/{langue} (brochures françaises seulement).
 *
 * Les slugs des brochures existantes reprennent le nom de leur PDF
 * (carte-touristique-verdon-tourisme), sinon leur titre.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('images', function (Blueprint $table) {
            $table->string('language', 2)->default('fr')->after('title');
            $table->foreignId('version_of_id')->nullable()->after('language')
                ->constrained('images')->nullOnDelete();
            $table->json('extra_languages')->nullable()->after('version_of_id');
            $table->string('slug')->nullable()->unique()->after('extra_languages');
            $table->index(['version_of_id', 'language']);
        });

        $used = [];
        DB::table('images')->orderBy('id')->select(['id', 'title', 'name', 'pdf_path'])
            ->get()
            ->each(function ($image) use (&$used) {
                $base = $image->pdf_path
                    ? Str::slug(pathinfo($image->pdf_path, PATHINFO_FILENAME))
                    : Str::slug($image->title ?: pathinfo((string) $image->name, PATHINFO_FILENAME));
                $base = Str::limit($base ?: 'brochure', 180, '');

                $slug = $base;
                $n = 2;
                while (isset($used[$slug])) {
                    $slug = $base.'-'.$n++;
                }
                $used[$slug] = true;

                DB::table('images')->where('id', $image->id)->update(['slug' => $slug]);
            });
    }

    public function down(): void
    {
        Schema::table('images', function (Blueprint $table) {
            $table->dropIndex(['version_of_id', 'language']);
            $table->dropConstrainedForeignId('version_of_id');
            $table->dropUnique(['slug']);
            $table->dropColumn(['language', 'extra_languages', 'slug']);
        });
    }
};
