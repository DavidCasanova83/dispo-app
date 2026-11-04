<?php

namespace App\Services;

use Illuminate\Support\Str;

/**
 * Reprise des anciennes brochures traduites (avant les versions linguistiques) :
 * la langue n'était écrite que dans le titre (« English - Hôtels »,
 * « English Version - Magazine… », « Carte touristique… IT », « … FR/EN »).
 *
 * - detect() : langue d'un titre (et du nom de son PDF), langues contenues
 *   (FR/EN), titre sans la mention de langue ;
 * - match()  : brochure française la plus proche de chaque document EN / IT
 *   (mots du titre en commun, même catégorie en bonus).
 *
 * PHP pur, sans base de données (tests : tests/Feature/BrochureVersionsTest.php).
 */
class BrochureLanguageMatcher
{
    /** Score minimal pour proposer un rattachement. */
    public const MIN_SCORE = 0.5;

    /** Mots sans valeur pour comparer deux titres. */
    private const IGNORED_WORDS = [
        'et', 'de', 'des', 'du', 'la', 'le', 'les', 'l', 'd', 'a', 'au', 'aux', 'en', 'un', 'une',
        'the', 'and', 'of', 'to', 'in', 'for',
        'english', 'version', 'italiano', 'versione', 'italiana', 'fr', 'it', 'eng',
        'hebergement', 'verdon', 'tourisme',
    ];

    /**
     * @return array{language: string, title: string, extra_languages: array<int, string>}
     */
    public function detect(?string $title, ?string $pdfPath = null): array
    {
        $title = trim((string) $title);
        $file = Str::slug(pathinfo((string) $pdfPath, PATHINFO_FILENAME));

        // Document bilingue : « … FR/EN », « … FR - EN », « … FR/EN/IT »
        if (preg_match('~\s*[-–(]?\s*\bFR\s*/\s*(EN|IT)(?:\s*/\s*(EN|IT))?\s*\)?\s*$~i', $title, $m)) {
            $extra = array_values(array_unique(array_filter([strtolower($m[1]), strtolower($m[2] ?? '')])));

            return ['language' => 'fr', 'title' => $this->clean(substr($title, 0, -strlen($m[0]))), 'extra_languages' => $extra];
        }

        // Mention en tête : « English - … », « English Version - … », « Italiano - … »
        $prefixes = [
            'en' => '~^\s*english(?:\s+version)?\s*[-–:|]\s*~i',
            'it' => '~^\s*(?:italiano|versione\s+italiana)\s*[-–:|]\s*~i',
        ];
        foreach ($prefixes as $language => $pattern) {
            if (preg_match($pattern, $title, $m)) {
                return ['language' => $language, 'title' => $this->clean(substr($title, strlen($m[0]))), 'extra_languages' => []];
            }
        }

        // Mention en fin : « … IT », « … - EN », « … (english) », « … (italiano) »
        $suffixes = [
            'en' => ['~(?:\s*[-–]\s*|\s*\(\s*|\s+)(?:EN|ENG)\s*\)?\s*$~', '~(?:\s*[-–]\s*|\s*\(\s*|\s+)english\s*\)?\s*$~i'],
            'it' => ['~(?:\s*[-–]\s*|\s*\(\s*|\s+)IT\s*\)?\s*$~', '~(?:\s*[-–]\s*|\s*\(\s*|\s+)italiano\s*\)?\s*$~i'],
        ];
        foreach ($suffixes as $language => $patterns) {
            foreach ($patterns as $pattern) {
                if (preg_match($pattern, $title, $m)) {
                    return ['language' => $language, 'title' => $this->clean(substr($title, 0, -strlen($m[0]))), 'extra_languages' => []];
                }
            }
        }

        // Sinon, le nom du fichier (english-hotels.pdf, guide-pratique-it.pdf)
        if ($file !== '' && (str_starts_with($file, 'english-') || str_ends_with($file, '-en') || str_ends_with($file, '-english'))) {
            return ['language' => 'en', 'title' => $title, 'extra_languages' => []];
        }
        if ($file !== '' && (str_starts_with($file, 'italiano-') || str_ends_with($file, '-it') || str_ends_with($file, '-italiano'))) {
            return ['language' => 'it', 'title' => $title, 'extra_languages' => []];
        }

        return ['language' => 'fr', 'title' => $title, 'extra_languages' => []];
    }

    /**
     * Ressemblance de deux titres (0 à 1) : mots communs / mots des deux.
     */
    public function similarity(string $a, string $b): float
    {
        $wordsA = $this->words($a);
        $wordsB = $this->words($b);
        if ($wordsA === [] || $wordsB === []) {
            return 0.0;
        }

        $common = count(array_intersect($wordsA, $wordsB));

        return $common / count(array_unique(array_merge($wordsA, $wordsB)));
    }

    /**
     * Brochure française proposée pour chaque document EN / IT.
     *
     * @param  array<int, array{id: int, title: string, category_id: ?int}>  $documents  documents EN / IT (titre sans la mention de langue)
     * @param  array<int, array{id: int, title: string, category_id: ?int}>  $brochures  brochures françaises
     * @return array<int, array{brochure_id: ?int, score: float, second_score: float}> par id de document
     */
    public function match(array $documents, array $brochures): array
    {
        $result = [];

        foreach ($documents as $document) {
            $scores = [];
            foreach ($brochures as $brochure) {
                $score = $this->similarity($document['title'], $brochure['title']);
                if ($score > 0 && $document['category_id'] && $document['category_id'] === $brochure['category_id']) {
                    $score += 0.1;
                }
                $scores[$brochure['id']] = round($score, 3);
            }
            arsort($scores);

            $best = array_key_first($scores);
            $bestScore = $best !== null ? $scores[$best] : 0.0;
            $secondScore = (float) (array_values($scores)[1] ?? 0.0);

            // Pas de proposition si trop éloigné ou à égalité avec une autre brochure
            $result[$document['id']] = [
                'brochure_id' => $best !== null && $bestScore >= self::MIN_SCORE && $bestScore > $secondScore ? $best : null,
                'score' => $bestScore,
                'second_score' => $secondScore,
            ];
        }

        return $result;
    }

    /**
     * @return array<int, string>
     */
    private function words(string $title): array
    {
        $words = [];
        foreach (explode('-', Str::slug($title)) as $word) {
            if ($word === '' || in_array($word, self::IGNORED_WORDS, true)) {
                continue;
            }
            // Pluriel → singulier (campings, cars, alentours, hôtels)
            if (strlen($word) > 3 && preg_match('~[sx]$~', $word)) {
                $word = substr($word, 0, -1);
            }
            if (! in_array($word, self::IGNORED_WORDS, true)) {
                $words[] = $word;
            }
        }

        return array_values(array_unique($words));
    }

    private function clean(string $title): string
    {
        return trim(preg_replace('~\s+~', ' ', trim($title, " \t-–:|")));
    }
}
