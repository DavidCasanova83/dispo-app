<?php

namespace App\Livewire\Concerns;

use Illuminate\Database\Eloquent\Builder;
use Livewire\Attributes\Url;

/**
 * Tri de colonnes réutilisable pour les tableaux Livewire paginés.
 *
 * Le composant hôte doit implémenter :
 *  - sortableFields() : la whitelist des champs triables. Le champ venant de
 *    l'URL n'est jamais injecté tel quel dans la requête : s'il n'est pas dans
 *    cette liste, on retombe sur le tri par défaut.
 *  - applyDefaultSorting() : le tri appliqué quand aucune colonne n'est choisie.
 *
 * Une entrée de sortableFields() vaut soit un nom de colonne, soit un callable
 * (Builder $query, string $direction) pour les tris qui demandent une
 * expression SQL (FIELD(), COALESCE()…).
 */
trait WithSorting
{
    #[Url(as: 'tri', except: '')]
    public string $sortField = '';

    #[Url(as: 'sens', except: 'asc')]
    public string $sortDirection = 'asc';

    /**
     * @return array<string, string|callable>
     */
    abstract protected function sortableFields(): array;

    abstract protected function applyDefaultSorting(Builder $query): Builder;

    public function sortBy(string $field): void
    {
        if (! array_key_exists($field, $this->sortableFields())) {
            return;
        }

        if ($this->sortField === $field) {
            $this->sortDirection = $this->sortDirection === 'asc' ? 'desc' : 'asc';
        } else {
            $this->sortField = $field;
            $this->sortDirection = $this->defaultDirectionFor($field);
        }

        $this->resetPage();
    }

    public function clearSorting(): void
    {
        $this->sortField = '';
        $this->sortDirection = 'asc';
        $this->resetPage();
    }

    /**
     * Direction naturelle au premier clic : les dates et les compteurs sont
     * plus utiles en décroissant (le plus récent / le plus nombreux d'abord),
     * le texte en croissant.
     */
    protected function defaultDirectionFor(string $field): string
    {
        return in_array($field, $this->descendingFirstFields(), true) ? 'desc' : 'asc';
    }

    /**
     * @return string[]
     */
    protected function descendingFirstFields(): array
    {
        return [];
    }

    protected function applySorting(Builder $query): Builder
    {
        $fields = $this->sortableFields();

        if ($this->sortField === '' || ! isset($fields[$this->sortField])) {
            return $this->applyDefaultSorting($query);
        }

        // Verrouillé sur deux valeurs : jamais de chaîne libre dans le SQL.
        $direction = $this->sortDirection === 'desc' ? 'desc' : 'asc';
        $spec = $fields[$this->sortField];

        if (is_callable($spec)) {
            $spec($query, $direction);

            return $query;
        }

        return $query->orderBy($spec, $direction);
    }

    /**
     * Etat d'une colonne pour l'affichage de l'indicateur de tri.
     */
    public function sortStateFor(string $field): ?string
    {
        return $this->sortField === $field ? $this->sortDirection : null;
    }
}
