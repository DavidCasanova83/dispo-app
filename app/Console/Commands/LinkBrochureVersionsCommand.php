<?php

namespace App\Console\Commands;

use App\Models\BrochureMenuItem;
use App\Models\Image;
use App\Models\SubCategory;
use App\Services\BrochureLanguageMatcher;
use App\Services\BrochureVersionService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Route;
use InvalidArgumentException;
use Throwable;

/**
 * Reprise des brochures traduites saisies avant les versions linguistiques :
 * « English - Hôtels » devient la version anglaise de « Hôtels », « Carte
 * touristique… IT » la version italienne de la carte, et « … FR/EN » un
 * document français qui contient aussi l'anglais.
 *
 * Sans --appliquer : aperçu seulement, rien n'est modifié.
 */
class LinkBrochureVersionsCommand extends Command
{
    protected $signature = 'brochures:lier-versions
        {--appliquer : Rattacher les versions proposées et nettoyer les titres}
        {--paire=* : Rattachement imposé « id_document:id_brochure » (ex. --paire=89:12), langue lue dans le titre}';

    protected $description = 'Rattache les anciennes brochures EN / IT (« English - … », « … IT ») à leur brochure française';

    public function handle(BrochureLanguageMatcher $matcher, BrochureVersionService $versions): int
    {
        $apply = (bool) $this->option('appliquer');

        // Code déployé mais cache des routes d'avant : le JSON des brochures
        // (adresses permanentes) ne peut pas être refait. Rien n'est modifié.
        if (! Route::has('brochure.permalink')) {
            $this->error('La route brochure.permalink est introuvable : le cache des routes date d\'avant le déploiement.');
            $this->line('Lancer d\'abord « php artisan app:deploy-command » (ou « php artisan route:clear »), puis relancer cette commande.');

            return self::FAILURE;
        }

        $brochures = Image::main()->orderBy('id')->get();

        // 1. Langue de chaque brochure d'après son titre (et le nom de son PDF)
        $documents = [];   // EN / IT à rattacher
        $frenchTitles = []; // brochures françaises, titre sans « FR/EN »
        $bilingual = [];   // id => [titre nettoyé, langues contenues]
        foreach ($brochures as $brochure) {
            $detected = $matcher->detect($brochure->title ?? $brochure->name, $brochure->pdf_path);
            if ($detected['language'] === 'fr') {
                $frenchTitles[$brochure->id] = $detected['title'];
                if ($detected['extra_languages'] !== []) {
                    $bilingual[$brochure->id] = $detected;
                }
            } else {
                $documents[$brochure->id] = $detected;
            }
        }

        if ($documents === [] && $bilingual === []) {
            $this->info('Aucune brochure EN / IT à rattacher.');

            return self::SUCCESS;
        }

        // 2. Brochure française proposée pour chaque document
        $matches = $matcher->match(
            collect($documents)->map(fn ($d, $id) => [
                'id' => $id, 'title' => $d['title'], 'category_id' => $brochures->find($id)->category_id,
            ])->values()->all(),
            collect($frenchTitles)->map(fn ($title, $id) => [
                'id' => $id, 'title' => $title, 'category_id' => $brochures->find($id)->category_id,
            ])->values()->all(),
        );

        // Rattachements imposés (--paire=document:brochure)
        foreach ((array) $this->option('paire') as $pair) {
            if (! preg_match('~^(\d+):(\d+)$~', trim($pair), $m)) {
                $this->error("Paire invalide « {$pair} » : attendu id_document:id_brochure.");

                return self::FAILURE;
            }
            [$documentId, $brochureId] = [(int) $m[1], (int) $m[2]];
            if (! isset($documents[$documentId])) {
                $this->error("Le document {$documentId} n'a pas de langue EN / IT dans son titre (ou n'existe pas).");

                return self::FAILURE;
            }
            if (! isset($frenchTitles[$brochureId])) {
                $this->error("La brochure {$brochureId} n'est pas une brochure française.");

                return self::FAILURE;
            }
            $matches[$documentId] = ['brochure_id' => $brochureId, 'score' => 1.0, 'second_score' => 0.0, 'forced' => true];
        }

        // 3. Une version par langue et par brochure : en cas de doublon, la plus
        //    proche, puis la plus récente (année d'édition, puis dernier ajout)
        $chosen = [];
        $duplicates = [];
        $existing = Image::whereNotNull('version_of_id')->get(['version_of_id', 'language'])
            ->mapWithKeys(fn ($v) => [$v->version_of_id.':'.$v->language => true]);
        $ranked = collect($matches)->filter(fn ($m) => $m['brochure_id'])
            ->sortByDesc(fn ($m, $id) => sprintf('%05.3f-%04d-%010d', $m['score'], (int) $brochures->find($id)->edition_year, $id));
        foreach ($ranked as $documentId => $match) {
            $key = $match['brochure_id'].':'.$documents[$documentId]['language'];
            if (isset($chosen[$key]) || isset($existing[$key])) {
                $duplicates[$documentId] = $match['brochure_id'];

                continue;
            }
            $chosen[$key] = $documentId;
        }

        // 4. Aperçu
        $rows = [];
        foreach ($documents as $documentId => $detected) {
            $document = $brochures->find($documentId);
            $match = $matches[$documentId];
            $target = $match['brochure_id'] ? $brochures->find($match['brochure_id']) : null;
            $status = match (true) {
                ! $target => 'à rattacher à la main',
                isset($duplicates[$documentId]) => 'doublon : non rattaché',
                default => ! empty($match['forced']) ? 'imposé' : 'proposé',
            };
            $rows[] = [
                $documentId,
                $document->title ?? $document->name,
                strtoupper($detected['language']),
                $target ? "{$target->id} · ".($target->title ?? $target->name) : '—',
                number_format($match['score'], 2),
                $status,
            ];
        }
        $this->table(['Id', 'Document', 'Langue', 'Brochure française', 'Score', 'Statut'], $rows);

        if ($bilingual !== []) {
            $this->line('Documents bilingues (gardés en français, langues ajoutées) :');
            foreach ($bilingual as $id => $detected) {
                $brochure = $brochures->find($id);
                $this->line("  {$id} · {$brochure->title} → « {$detected['title']} » + ".strtoupper(implode('/', $detected['extra_languages'])));
            }
        }

        $unmatched = collect($rows)->where(5, 'à rattacher à la main');
        if ($unmatched->isNotEmpty()) {
            $this->warn('Sans brochure française trouvée : rattacher depuis la fenêtre de modification de la brochure '
                .'(« Versions linguistiques » > « Ou rattacher un document déjà en ligne »), ou relancer avec --paire=id_document:id_brochure.');
        }
        if ($duplicates !== []) {
            $this->warn('Doublons : la brochure a déjà une version dans cette langue ; vérifier puis supprimer le document en trop.');
        }

        if (! $apply) {
            $this->newLine();
            $this->info('Aperçu seulement : relancer avec --appliquer pour rattacher '.count($chosen).' version(s).');

            return self::SUCCESS;
        }

        // 5. Application
        $linked = 0;
        DB::transaction(function () use ($chosen, $documents, $brochures, $bilingual, $versions, &$linked) {
            foreach ($chosen as $key => $documentId) {
                [$brochureId, $language] = explode(':', $key);
                $document = $brochures->find($documentId);
                try {
                    $version = $versions->attach($brochures->find((int) $brochureId), $document, $language);
                } catch (InvalidArgumentException $e) {
                    $this->error("{$documentId} : {$e->getMessage()}");

                    continue;
                }
                // « English - Hôtels » → « Hôtels » (titre à traduire ensuite si besoin)
                $version->update(['title' => $documents[$documentId]['title'] ?: $version->title]);
                $linked++;
            }

            foreach ($bilingual as $id => $detected) {
                $brochures->find($id)->update([
                    'title' => $detected['title'],
                    'extra_languages' => $detected['extra_languages'],
                ]);
            }
        });

        $this->info("{$linked} version(s) rattachée(s), ".count($bilingual).' document(s) bilingue(s) mis à jour.');

        // Les rattachements sont enregistrés : un échec ici ne doit pas cacher la suite.
        try {
            Artisan::call('images:generate-json');
        } catch (Throwable $e) {
            $this->warn('Fichier images.json non refait ('.$e->getMessage().') : lancer « php artisan images:generate-json ».');
        }

        // 6. Sous-catégories vidées (ex. « English version » : les versions prennent
        //    le classement de leur brochure) et menus qui y mènent
        //    (toutes les sous-catégories vides : une reprise relancée les signale encore)
        $emptied = SubCategory::whereDoesntHave('images')->get();
        foreach ($emptied as $subCategory) {
            $this->warn("La sous-catégorie « {$subCategory->name} » est vide : la supprimer dans Brochures.");
            if (! $subCategory->slug) {
                continue;
            }
            BrochureMenuItem::where('url', 'like', '%/'.$subCategory->slug.'%')->get()
                ->each(fn ($item) => $this->warn("  Menu « {$item->title} » ({$item->url}) : le faire pointer vers /brochures-oti-vt?langue=en (ou ?langue=it)."));
        }

        return self::SUCCESS;
    }
}
