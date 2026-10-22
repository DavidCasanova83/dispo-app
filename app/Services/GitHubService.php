<?php

namespace App\Services;

use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;

/**
 * Accès à l'API GitHub du dépôt du site (demandes à Claude).
 *
 * Jeton fine-grained limité à ce dépôt (CLAUDE_GITHUB_TOKEN) : « Issues » et
 * « Pull requests » en lecture et écriture, « Contents » en lecture.
 * Les erreurs sont levées en \DomainException avec un message lisible.
 */
class GitHubService
{
    public function isConfigured(): bool
    {
        return (string) config('services.github_claude.token') !== '';
    }

    /** Dépôt « propriétaire/nom ». */
    public function repository(): string
    {
        return (string) config('services.github_claude.repository') ?: 'DavidCasanova83/VT-custom-2025';
    }

    /**
     * Appel à l'API ; $path est relatif au dépôt (« issues », « pulls/12 »…),
     * ou vide pour le dépôt lui-même.
     *
     * @throws \DomainException
     */
    public function call(string $method, string $path = '', array $data = []): array
    {
        if (! $this->isConfigured()) {
            throw new \DomainException('Connexion à GitHub non configurée : ajoutez CLAUDE_GITHUB_TOKEN dans le .env (voir DEMANDES_CLAUDE.md).');
        }

        $url = 'https://api.github.com/repos/'.$this->repository().($path !== '' ? '/'.ltrim($path, '/') : '');

        try {
            $response = Http::withToken((string) config('services.github_claude.token'))
                ->withHeaders([
                    'Accept' => 'application/vnd.github+json',
                    'X-GitHub-Api-Version' => '2022-11-28',
                    'User-Agent' => 'dispo-app-verdontourisme',
                ])
                ->timeout(20)
                ->send($method, $url, $data === [] ? [] : ['json' => $data]);
        } catch (ConnectionException $e) {
            throw new \DomainException('GitHub injoignable : '.$e->getMessage());
        }

        if ($response->successful()) {
            return (array) $response->json();
        }

        $explanations = [
            401 => 'jeton GitHub refusé (expiré ou mal copié dans le .env)',
            403 => 'droits insuffisants : le jeton doit avoir « Issues » et « Pull requests » en lecture et écriture, « Contents » en lecture, sur ce dépôt',
            404 => 'introuvable pour ce jeton (nom du dépôt, ou jeton sans accès à ce dépôt)',
            410 => 'les issues sont désactivées sur ce dépôt',
            422 => 'demande refusée par GitHub (contenu invalide)',
        ];
        $detail = $response->json('message');

        throw new \DomainException(sprintf(
            'GitHub a répondu %d : %s%s',
            $response->status(),
            $explanations[$response->status()] ?? 'erreur inattendue',
            is_string($detail) && $detail !== '' ? " (« {$detail} »)" : ''
        ), $response->status());
    }

    /** Branche par défaut du dépôt (base des PR), gardée un jour. */
    public function defaultBranch(): string
    {
        return Cache::remember('github_claude.default_branch.'.$this->repository(), now()->addDay(), function () {
            return (string) ($this->call('GET')['default_branch'] ?? '') ?: 'main';
        });
    }

    /**
     * Test de la configuration (lecture seule).
     *
     * @throws \DomainException
     */
    public function test(): string
    {
        $repo = $this->call('GET');

        if (empty($repo['has_issues'])) {
            throw new \DomainException('Les issues sont désactivées sur ce dépôt (Settings > General > Features > Issues).');
        }

        return sprintf(
            'Connexion à GitHub réussie : dépôt %s (%s), branche par défaut %s.',
            $repo['full_name'] ?? $this->repository(),
            ! empty($repo['private']) ? 'privé' : 'public : les demandes y seront visibles de tous',
            $repo['default_branch'] ?? '?'
        );
    }
}
