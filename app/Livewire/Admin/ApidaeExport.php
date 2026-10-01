<?php

namespace App\Livewire\Admin;

use App\Exports\ApidaeExportData;
use App\Exports\ApidaeWorkbookExport;
use App\Services\ApidaeService;
use App\Services\MailjetContactsService;
use Illuminate\Support\Facades\Log;
use Livewire\Component;
use Maatwebsite\Excel\Facades\Excel;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ApidaeExport extends Component
{
    public string $search = '';

    /** @var array<int, int> Identifiants des sélections cochées. */
    public array $selected = [];

    /** Onglet téléchargé par le bouton CSV. */
    public string $csvSheet = ApidaeExportData::SHEET_EMAILS;

    /** Liste Mailjet ciblée par la synchronisation. */
    public ?int $mailjetListId = null;

    /**
     * Résultat du dry-run, en attente de confirmation.
     *
     * @var array{ajouts: int, retraits: int, desinscrits_conserves: int, inchanges: int, erreurs: array<int, string>}|null
     */
    public ?array $mailjetPreview = null;

    /** @var array<int, array{id: int, nom: string, abonnes: int}>|null */
    private ?array $mailjetListsCache = null;

    public bool $isExporting = false;

    /**
     * Sélections Apidae, mises en cache le temps de la requête pour éviter
     * de rappeler l'API à chaque re-render de Livewire.
     *
     * @var array<int, array{id: int, nom: string}>|null
     */
    private ?array $selectionsCache = null;

    public function getSelectionsProperty(): array
    {
        if ($this->selectionsCache === null) {
            $this->selectionsCache = app(ApidaeService::class)->selections();
        }

        return $this->selectionsCache;
    }

    /**
     * @return array<int, array{id: int, nom: string, abonnes: int}>
     */
    public function getMailjetListsProperty(): array
    {
        if (! auth()->user()?->can('sync-mailjet')) {
            return [];
        }

        if ($this->mailjetListsCache === null) {
            $this->mailjetListsCache = app(MailjetContactsService::class)->lists();
        }

        return $this->mailjetListsCache;
    }

    public function getFilteredSelectionsProperty(): array
    {
        $search = trim($this->search);

        if ($search === '') {
            return $this->selections;
        }

        return array_values(array_filter(
            $this->selections,
            fn ($selection) => mb_stripos($selection['nom'], $search) !== false
        ));
    }

    public function toggleAllFiltered(): void
    {
        $filteredIds = array_column($this->filteredSelections, 'id');
        $alreadyAllSelected = empty(array_diff($filteredIds, $this->selected));

        if ($alreadyAllSelected) {
            $this->selected = array_values(array_diff($this->selected, $filteredIds));
        } else {
            $this->selected = array_values(array_unique(array_merge($this->selected, $filteredIds)));
        }
    }

    public function clearSelection(): void
    {
        $this->selected = [];
    }

    /**
     * Télécharge un seul onglet au format CSV.
     */
    public function export(?string $sheet = null): ?StreamedResponse
    {
        $sheet = $sheet ?? $this->csvSheet;

        $rows = $this->collectRows();

        if ($rows === null) {
            return null;
        }

        ['headings' => $headings, 'rows' => $rows] = ApidaeExportData::sheet($sheet, $rows);

        $this->logExport('CSV ('.$sheet.')', count($rows));

        $slug = $sheet === ApidaeExportData::SHEET_EMAILS ? 'emails' : 'details';
        $filename = 'export-apidae-'.$slug.'-'.now()->format('Y-m-d-His').'.csv';

        return response()->streamDownload(function () use ($headings, $rows) {
            $handle = fopen('php://output', 'w');

            // BOM UTF-8 pour qu'Excel ouvre correctement les accents.
            fwrite($handle, "\xEF\xBB\xBF");

            fputcsv($handle, $headings, ';', '"', '\\');

            foreach ($rows as $row) {
                fputcsv($handle, $row, ';', '"', '\\');
            }

            fclose($handle);
        }, $filename, ['Content-Type' => 'text/csv; charset=UTF-8']);
    }

    /**
     * Export Excel : onglet "Emails" puis onglet "Détails".
     */
    public function exportXlsx(): ?BinaryFileResponse
    {
        $rows = $this->collectRows();

        if ($rows === null) {
            return null;
        }

        $this->logExport('XLSX', count($rows));

        return Excel::download(
            new ApidaeWorkbookExport($rows),
            'export-apidae-'.now()->format('Y-m-d-His').'.xlsx'
        );
    }

    /**
     * Interroge Apidae pour toutes les sélections cochées.
     *
     * @return array<int, array{0: string, 1: string, 2: string, 3: string}>|null
     *                                                                            null si aucune sélection n'est cochée.
     */
    private function collectRows(): ?array
    {
        if (empty($this->selected)) {
            session()->flash('error', 'Veuillez cocher au moins une sélection.');

            return null;
        }

        $names = collect($this->selections)->pluck('nom', 'id');
        $rows = [];

        // Dédoublonnage global : un même email n'apparaît qu'une fois dans le fichier.
        $seenEmails = [];

        foreach ($this->selected as $selectionId) {
            $selectionName = $names[$selectionId] ?? (string) $selectionId;

            foreach ($this->rowsForSelection((int) $selectionId, $seenEmails) as $row) {
                $rows[] = [...$row, $selectionName];
            }
        }

        return $rows;
    }

    /**
     * Lignes [nom, email, origine] d'une sélection, contacts de fiche inclus.
     *
     * @param  array<string, true>  $seenEmails  Emails déjà exportés, mis à jour au passage.
     * @return array<int, array{0: string, 1: string, 2: string}>
     */
    private function rowsForSelection(int $selectionId, array &$seenEmails): array
    {
        $rows = [];

        foreach (app(ApidaeService::class)->objectsForSelection($selectionId) as $object) {
            $email = $object['email'];

            // Les fiches sans email restent dans l'onglet "Détails".
            if ($email === '' || ! isset($seenEmails[mb_strtolower($email)])) {
                if ($email !== '') {
                    $seenEmails[mb_strtolower($email)] = true;
                }

                $rows[] = [$object['nom'], $email, 'Fiche'];
            }

            // Une ligne supplémentaire par mail de contact de la fiche.
            foreach ($object['contacts'] as $contactEmail) {
                $key = mb_strtolower($contactEmail);

                if (isset($seenEmails[$key])) {
                    continue;
                }

                $seenEmails[$key] = true;

                $rows[] = [$object['nom'], $contactEmail, 'Contact'];
            }
        }

        return $rows;
    }

    /**
     * Dry-run : calcule le différentiel sans rien écrire sur Mailjet.
     */
    public function previewMailjetSync(): void
    {
        $this->authorize('sync-mailjet');

        $this->mailjetPreview = null;

        if (! $this->mailjetListId) {
            session()->flash('error', 'Veuillez choisir une liste Mailjet.');

            return;
        }

        $emails = $this->mailjetEmails();

        if ($emails === null) {
            return;
        }

        $this->mailjetPreview = app(MailjetContactsService::class)
            ->syncList($this->mailjetListId, $emails, dryRun: true);
    }

    public function cancelMailjetSync(): void
    {
        $this->mailjetPreview = null;
    }

    /**
     * Applique le différentiel sur la liste Mailjet.
     */
    public function confirmMailjetSync(): void
    {
        $this->authorize('sync-mailjet');

        if ($this->mailjetPreview === null || ! $this->mailjetListId) {
            session()->flash('error', 'Lancez d\'abord une prévisualisation.');

            return;
        }

        $emails = $this->mailjetEmails();

        if ($emails === null) {
            return;
        }

        $result = app(MailjetContactsService::class)
            ->syncList($this->mailjetListId, $emails, dryRun: false);

        $this->mailjetPreview = null;

        // La liste a changé : le nombre d'abonnés affiché doit être rechargé.
        $this->mailjetListsCache = null;

        if (! empty($result['erreurs'])) {
            session()->flash('error', 'Synchronisation partielle : '.implode(' / ', $result['erreurs']));

            return;
        }

        session()->flash('success', sprintf(
            '%d contact(s) ajouté(s), %d retiré(s), %d désinscrit(s) conservé(s).',
            $result['ajouts'],
            $result['retraits'],
            $result['desinscrits_conserves'],
        ));
    }

    /**
     * Les emails des sélections cochées, ou null si rien n'est exploitable.
     *
     * @return array<int, string>|null
     */
    private function mailjetEmails(): ?array
    {
        $rows = $this->collectRows();

        if ($rows === null) {
            return null;
        }

        $emails = array_column(ApidaeExportData::emailRows($rows), 0);

        if (empty($emails)) {
            session()->flash('error', 'Aucun email trouvé dans les sélections cochées.');

            return null;
        }

        return $emails;
    }

    private function logExport(string $format, int $lines): void
    {
        Log::info('Export Apidae '.$format, [
            'user_id' => auth()->id(),
            'selections' => $this->selected,
            'lignes' => $lines,
        ]);
    }

    public function render()
    {
        return view('livewire.admin.apidae-export');
    }
}
