# Vérification des pages

Relecture annuelle des pages françaises de verdontourisme.com par les collègues
(« relecteurs »), pilotée par l'admin. Les versions anglaise et italienne sont
vérifiées à part, par les traducteurs (module Traductions).

## Le circuit

1. **Entrée** : « Scanner le site » (sitemap des pages) ou « Ajouter une page ».
2. **Relecteurs** : l'admin assigne une ou plusieurs personnes. Elles sont mises
   en file d'attente.
3. **Distribution** : chaque nuit à 3 h 30 (`verification:release-pages`),
   chaque relecteur reçoit des pages jusqu'à en avoir N ouvertes en même temps
   (N réglable, 2 par défaut). « Distribuer maintenant » force le passage.
4. **Relecture** (FR uniquement) : rien à signaler → retour réglé d'office ;
   problème → retour « Nouveau » chez l'admin.
5. **Traitement** : « Réglé », « Je m'en occupe » (correction en cours) ou
   « Renvoyer au relecteur » (la page revient sur son tableau de bord), avec une
   réponse facultative.
6. **Prête à clôturer** : chaque relecteur assigné a un retour FR réglé.
7. **Clôture** (manuelle, une page ou en masse) : la page est vérifiée pour un
   an, ses relectures sont **archivées** dans le cycle, la page passe au cycle
   suivant.
8. **Renouvellement** : 365 jours après, à 3 h 00
   (`verification:revalidate-aged`), la page repart en relecture vers les
   mêmes relecteurs.

## Écrans

Menu **Vérification web > Vérification des pages** (permission
`manage-page-verification`), un bandeau des étapes commun à tous les onglets
(chaque compteur ouvre l'onglet Pages filtré sur l'étape) :

| Onglet | Adresse | Contenu |
|---|---|---|
| À traiter | `/verification/admin/a-traiter` | Nouveaux retours (problème cité, boutons d'action directs), pages prêtes à clôturer (clôture en masse), corrections en cours |
| Pages | `/verification/admin/pages` | Toutes les pages : étape, avancement par relecteur, échéance ; ajout, relecteurs, actions en masse (relecteurs, priorité, échéance, catégorie, distribution, retrait), pages plus sur le site, scan, réglage du plafond |
| Vérifiées | `/verification/admin/verifiees` | Pages clôturées, de la prochaine à repartir à la plus lointaine |
| Corbeille | `/verification/admin/corbeille` | Pages retirées du suivi, restaurables |
| Fiche | `/verification/admin/pages/{id}` | Retours du cycle (y compris les relecteurs sans réponse), relecteurs, traductions, cycles clôturés consultables, journal |

L'ancienne adresse `/verification/admin/retours` redirige vers « À traiter »
(ou vers la fiche avec `?page=ID`).

Étapes (`VerificationPage::STAGES`, même règle en SQL dans `stageSql()` et
`scopeInStage()`) : Sans relecteur · En attente de distribution · En relecture
· Retours à traiter · Correction en cours · Prête à clôturer · Vérifiée.

Relecteur : **Mes vérifications** (`/verification`), historique
(`/verification/historique`), formulaire de relecture (français seulement ; un
ancien lien `?lang=en` ouvre la relecture FR).

## Correction par Claude (super-admin)

Fiche d'une page, bloc « 🤖 Correction par Claude » : les retours signalés et
pas encore réglés sont envoyés à Claude (issue GitHub dans le dépôt du site,
sans le nom des relecteurs) ; Claude corrige les `contenu.*.md` de la page et
le panel crée la PR à relire. Réservé au super-admin. Détails et mise en
place : `DEMANDES_CLAUDE.md`.

## Pastilles (notifications dans l'app)

- Admin : pastille rouge sur « Vérification des pages » = nouveaux retours ;
  tuile du tableau de bord (nouveaux retours, pages prêtes à clôturer) ;
  compteur sur l'onglet À traiter.
- Relecteur : pastille sur « Mes vérifications » = pages à relire + réponses de
  l'équipe pas encore vues ; bandeau sur son tableau de bord ; « Nouveau » dans
  son historique. Une réponse est « vue » quand il ouvre l'historique ou le
  formulaire de la page (`verification_reviews.reviewer_seen_at`).

Aucun e-mail n'est envoyé.

## Conservation des données

Rien n'est supprimé par l'application :

- **Clôture** : les relectures restent en base avec `archived_at` et leur
  `cycle` ; `verification_pages.cycle` passe au suivant. L'unicité est
  page + relecteur + langue + cycle. La relation `reviews()` ne renvoie que le
  cycle en cours ; `allReviews()` et `archivedReviews()` donnent le reste.
- **Journal** (`verification_events`, `VerificationJournal`) : soumission (avec
  la version précédente d'une relecture renvoyée et la réponse de l'admin
  qu'elle remplaçait), traitement et réponse de l'admin, assignation et retrait
  d'un relecteur (avec ses dates), distribution, clôture, renouvellement,
  retrait du suivi, restauration, création. Les noms sont copiés dans `data`.
- **Retrait du suivi** : suppression douce (corbeille), relectures,
  assignations et journal conservés ; restauration depuis l'onglet Corbeille
  (ou `php artisan verification:restore-page {id}`).
- **Compte supprimé** : ses relectures restent (`user_id` passe à NULL,
  affichées « Utilisateur supprimé »).

Seule `php artisan verification:purge` efface tout, définitivement.

Les relectures supprimées par les clôtures faites avant la migration
`2026_10_27_100000_add_cycles_to_verification` ne sont plus en base ; seule
une sauvegarde antérieure permettrait de les retrouver.

## Réglage

Plafond de pages ouvertes par relecteur : onglet Pages, menu ⋯, « Réglages de
distribution » (table `settings`, clé `verification.active_pages_quota`,
1 à 20, 2 par défaut ; `PageReleaseService::quota()`).

## Mise en production

1. Sauvegarde complète de la base.
2. `php artisan migrate` : trois migrations additives
   (`2026_10_27_100000_add_cycles_to_verification`,
   `2026_10_27_100100_create_verification_events_table`,
   `2026_10_27_110000_create_verification_claude_requests_table`). Les relectures
   existantes passent au cycle 1, marquées vues, datées de leur création.
3. `php artisan app:deploy-command` (caches, `npm run build`).

## Tests

`php artisan test tests/Feature/Verification/PageVerificationTest.php` :
archivage à la clôture, cycles, journal, retrait d'un relecteur, compte
supprimé, corbeille, cohérence des étapes, plafond, traitement en un clic,
clôture en masse, fiche, relecture FR, pastilles.
