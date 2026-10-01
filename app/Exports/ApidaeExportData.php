<?php

namespace App\Exports;

/**
 * Définition des deux onglets de l'export Apidae, partagée entre le classeur
 * Excel et le téléchargement CSV (qui n'exporte qu'un onglet à la fois).
 */
class ApidaeExportData
{
    public const SHEET_EMAILS = 'Emails';

    public const SHEET_DETAILS = 'Détails';

    public const HEADINGS_EMAILS = ['Email'];

    public const HEADINGS_DETAILS = ['Nom', 'Email', 'Origine', 'Sélection'];

    /**
     * Onglet "Emails" : uniquement la colonne email, les lignes sans email exclues.
     *
     * @param  array<int, array{0: string, 1: string, 2: string, 3: string}>  $rows
     * @return array<int, array{0: string}>
     */
    public static function emailRows(array $rows): array
    {
        $emails = [];

        foreach ($rows as $row) {
            if ($row[1] !== '') {
                $emails[] = [$row[1]];
            }
        }

        return $emails;
    }

    /**
     * Les en-têtes et lignes d'un onglet donné.
     *
     * @param  array<int, array{0: string, 1: string, 2: string, 3: string}>  $rows
     * @return array{headings: array<int, string>, rows: array<int, array<int, string>>}
     */
    public static function sheet(string $name, array $rows): array
    {
        if ($name === self::SHEET_EMAILS) {
            return ['headings' => self::HEADINGS_EMAILS, 'rows' => self::emailRows($rows)];
        }

        return ['headings' => self::HEADINGS_DETAILS, 'rows' => $rows];
    }
}
