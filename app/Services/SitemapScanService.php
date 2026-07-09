<?php

namespace App\Services;

use App\Models\VerificationPage;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class SitemapScanService
{
    public function __construct(private readonly string $sitemapUrl) {}

    /**
     * Fetch + parse + diff. Renvoie un résumé exploitable côté UI.
     *
     * @return array{created:int, updated:int, marked_obsolete:int, total_in_sitemap:int}
     */
    public function scan(int $creatorUserId): array
    {
        $urls = $this->fetchUrls();

        $created = 0;
        $updated = 0;

        DB::transaction(function () use ($urls, $creatorUserId, &$created, &$updated) {
            $now = now();

            foreach ($urls as $url) {
                $existing = VerificationPage::where('url', $url)->first();

                if ($existing) {
                    $existing->update([
                        'last_seen_in_sitemap_at' => $now,
                        'is_in_sitemap' => true,
                    ]);
                    $updated++;
                } else {
                    VerificationPage::create([
                        'title' => $this->deriveTitleFromUrl($url),
                        'url' => $url,
                        'priority' => 'medium',
                        'status' => 'pending',
                        'is_in_sitemap' => true,
                        'last_seen_in_sitemap_at' => $now,
                        'created_by' => $creatorUserId,
                    ]);
                    $created++;
                }
            }
        });

        // Pages présentes en BDD qui n'ont pas été vues dans ce scan : marquées hors-sitemap.
        $markedObsolete = VerificationPage::where('is_in_sitemap', true)
            ->where(function ($q) {
                $q->whereNull('last_seen_in_sitemap_at')
                    ->orWhere('last_seen_in_sitemap_at', '<', now()->subMinutes(1));
            })
            ->update(['is_in_sitemap' => false]);

        return [
            'created' => $created,
            'updated' => $updated,
            'marked_obsolete' => $markedObsolete,
            'total_in_sitemap' => count($urls),
        ];
    }

    /**
     * Réinitialise le tracking sitemap pour toutes les pages.
     * Aucune page n'est supprimée, aucune assignation/review n'est touchée :
     * on remet seulement à zéro les indicateurs is_in_sitemap et last_seen_in_sitemap_at.
     *
     * @return int Nombre de pages réinitialisées
     */
    public function resetTracking(): int
    {
        return VerificationPage::query()
            ->where(function ($q) {
                $q->where('is_in_sitemap', true)
                    ->orWhereNotNull('last_seen_in_sitemap_at');
            })
            ->update([
                'is_in_sitemap' => false,
                'last_seen_in_sitemap_at' => null,
            ]);
    }

    /**
     * @return string[]
     */
    private function fetchUrls(): array
    {
        $response = Http::timeout(30)
            ->withHeaders(['Accept' => 'application/xml,text/xml'])
            ->get($this->sitemapUrl);

        if (! $response->successful()) {
            throw new \RuntimeException("Impossible de récupérer le sitemap (HTTP {$response->status()}).");
        }

        $body = $response->body();

        // Désactivation du chargement d'entités externes (sécurité XXE).
        $previous = libxml_use_internal_errors(true);
        $xml = simplexml_load_string($body);
        libxml_use_internal_errors($previous);

        if ($xml === false) {
            throw new \RuntimeException('Sitemap XML invalide ou illisible.');
        }

        $urls = [];
        foreach ($xml->url ?? [] as $node) {
            $loc = trim((string) ($node->loc ?? ''));
            if ($loc === '' || ! filter_var($loc, FILTER_VALIDATE_URL)) {
                continue;
            }

            // On ne garde que les pages françaises : exclure /en/ et /it/.
            $path = parse_url($loc, PHP_URL_PATH) ?? '';
            if (preg_match('#^/(en|it)(/|$)#i', $path)) {
                continue;
            }

            $urls[] = $loc;
        }

        return array_values(array_unique($urls));
    }

    private function deriveTitleFromUrl(string $url): string
    {
        $path = parse_url($url, PHP_URL_PATH) ?? '';
        $segment = trim($path, '/');
        if ($segment === '') {
            return 'Accueil';
        }

        $last = basename($segment);
        $title = Str::of($last)
            ->replace(['-', '_'], ' ')
            ->trim()
            ->ucfirst()
            ->__toString();

        return $title !== '' ? $title : $url;
    }
}
