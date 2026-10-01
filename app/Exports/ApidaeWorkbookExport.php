<?php

namespace App\Exports;

use App\Exports\Sheets\ApidaeSheet;
use Maatwebsite\Excel\Concerns\WithMultipleSheets;

/**
 * Classeur Excel de l'export Apidae : un onglet "Emails" prêt à copier-coller
 * dans un outil d'emailing, et un onglet "Détails" avec toutes les colonnes.
 */
class ApidaeWorkbookExport implements WithMultipleSheets
{
    /**
     * @param  array<int, array{0: string, 1: string, 2: string, 3: string}>  $rows
     */
    public function __construct(private array $rows) {}

    public function sheets(): array
    {
        return [
            new ApidaeSheet(
                ApidaeExportData::SHEET_EMAILS,
                ApidaeExportData::HEADINGS_EMAILS,
                ApidaeExportData::emailRows($this->rows),
            ),
            new ApidaeSheet(
                ApidaeExportData::SHEET_DETAILS,
                ApidaeExportData::HEADINGS_DETAILS,
                $this->rows,
            ),
        ];
    }
}
