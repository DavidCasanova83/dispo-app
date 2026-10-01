<?php

namespace App\Services;

use Illuminate\Support\Facades\Log;
use Mailjet\Client;
use Mailjet\Resources;

/**
 * Gestion des listes de contacts Mailjet (API v3).
 *
 * MailjetService utilise la v3.1, réservée à l'envoi de messages : les listes
 * de contacts n'existent que sur la v3, d'où ce client distinct.
 */
class MailjetContactsService
{
    /** Nombre de contacts envoyés par requête à managemanycontacts. */
    private const BATCH_SIZE = 500;

    /** Nombre de destinataires lus par page sur /listrecipient. */
    private const PAGE_SIZE = 1000;

    private Client $client;

    public function __construct()
    {
        $this->client = new Client(
            config('services.mailjet.key'),
            config('services.mailjet.secret'),
            true,
            ['version' => 'v3']
        );
    }

    /**
     * Les listes de contacts du compte, hors listes supprimées.
     *
     * @return array<int, array{id: int, nom: string, abonnes: int}>
     */
    public function lists(): array
    {
        $response = $this->client->get(Resources::$Contactslist, ['filters' => ['Limit' => 100]]);

        if (! $response->success()) {
            Log::error('Mailjet : lecture des listes impossible', ['error' => $response->getReasonPhrase()]);

            return [];
        }

        return collect($response->getData())
            ->reject(fn ($list) => (bool) ($list['IsDeleted'] ?? false))
            ->map(fn ($list) => [
                'id' => (int) $list['ID'],
                'nom' => $list['Name'] ?? ('Liste '.$list['ID']),
                'abonnes' => (int) ($list['SubscriberCount'] ?? 0),
            ])
            ->sortBy('nom', SORT_NATURAL | SORT_FLAG_CASE)
            ->values()
            ->all();
    }

    /**
     * Les contacts actuellement rattachés à une liste, indexés par email en minuscules.
     *
     * @return array<string, array{email: string, unsubscribed: bool}>
     */
    public function listRecipients(int $listId): array
    {
        $recipients = [];
        $offset = 0;

        do {
            $response = $this->client->get(Resources::$Listrecipient, ['filters' => [
                'ContactsList' => $listId,
                'ShowContactsInfo' => true,
                'Limit' => self::PAGE_SIZE,
                'Offset' => $offset,
            ]]);

            if (! $response->success()) {
                Log::error('Mailjet : lecture des destinataires impossible', [
                    'list_id' => $listId,
                    'error' => $response->getReasonPhrase(),
                ]);

                break;
            }

            $page = $response->getData();

            foreach ($page as $recipient) {
                $email = trim($recipient['Email'] ?? '');

                if ($email === '') {
                    continue;
                }

                $recipients[mb_strtolower($email)] = [
                    'email' => $email,
                    'unsubscribed' => (bool) ($recipient['IsUnsubscribed'] ?? false),
                ];
            }

            $offset += self::PAGE_SIZE;
        } while (count($page) === self::PAGE_SIZE);

        return $recipients;
    }

    /**
     * Aligne une liste Mailjet sur un jeu d'emails.
     *
     * Les contacts déjà désinscrits ne sont jamais retirés ni réabonnés :
     * les retirer effacerait leur désinscription côté Mailjet, et ils
     * reviendraient abonnés lors d'une prochaine synchronisation.
     *
     * @param  array<int, string>  $emails  Emails issus des sélections Apidae.
     * @return array{ajouts: int, retraits: int, desinscrits_conserves: int, inchanges: int, erreurs: array<int, string>}
     */
    public function syncList(int $listId, array $emails, bool $dryRun = true): array
    {
        $wanted = [];

        foreach ($emails as $email) {
            $email = trim($email);

            if ($email !== '' && filter_var($email, FILTER_VALIDATE_EMAIL)) {
                $wanted[mb_strtolower($email)] = $email;
            }
        }

        $current = $this->listRecipients($listId);

        $toAdd = array_values(array_diff_key($wanted, $current));

        $toRemove = [];
        $keptUnsubscribed = 0;

        foreach ($current as $key => $recipient) {
            if (isset($wanted[$key])) {
                continue;
            }

            if ($recipient['unsubscribed']) {
                $keptUnsubscribed++;

                continue;
            }

            $toRemove[] = $recipient['email'];
        }

        $result = [
            'ajouts' => count($toAdd),
            'retraits' => count($toRemove),
            'desinscrits_conserves' => $keptUnsubscribed,
            'inchanges' => count(array_intersect_key($wanted, $current)),
            'erreurs' => [],
        ];

        if ($dryRun) {
            return $result;
        }

        // "addnoforce" ajoute les nouveaux contacts sans réabonner ceux qui
        // se sont désinscrits ; "addforce" le ferait et est donc proscrit.
        $result['erreurs'] = array_merge(
            $this->manageContacts($listId, $toAdd, 'addnoforce'),
            $this->manageContacts($listId, $toRemove, 'remove'),
        );

        Log::info('Mailjet : synchronisation de liste', ['list_id' => $listId] + $result);

        return $result;
    }

    /**
     * Applique une action à des contacts, par lots.
     *
     * @param  array<int, string>  $emails
     * @return array<int, string> Messages d'erreur éventuels.
     */
    private function manageContacts(int $listId, array $emails, string $action): array
    {
        $errors = [];

        foreach (array_chunk($emails, self::BATCH_SIZE) as $chunk) {
            $response = $this->client->post(Resources::$ContactManagemanycontacts, ['body' => [
                'ContactsLists' => [
                    ['ListID' => $listId, 'Action' => $action],
                ],
                'Contacts' => array_map(fn ($email) => ['Email' => $email], $chunk),
            ]]);

            if (! $response->success()) {
                $message = $response->getReasonPhrase();

                Log::error('Mailjet : lot en échec', [
                    'list_id' => $listId,
                    'action' => $action,
                    'contacts' => count($chunk),
                    'status' => $response->getStatus(),
                    'error' => $message,
                ]);

                $errors[] = $action.' : '.$message;
            }
        }

        return $errors;
    }
}
