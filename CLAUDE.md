# CLAUDE.md

Guide pour Claude Code (et tout développeur) travaillant sur ce dépôt.
Langue du projet : **français** (code métier, commentaires, commits, UI).

## Le projet en bref

**DISPO-APP** est le panel interne de **Verdon Tourisme** (Office de Tourisme Intercommunal).
Application Laravel 12 + Livewire 3 (Flux UI, Volt) regroupant plusieurs modules :

| Module | Rôle | Point d'entrée |
|---|---|---|
| Disponibilités | Hébergements importés d'Apidae, e-mails Mailjet quotidiens demandant aux hébergeurs leur disponibilité (lien signé), suivi des réponses | `/accommodations`, `app/Console/Commands/FetchApidaeData.php`, `SendAvailabilityEmails.php`, `AccommodationResponseController` |
| Qualification | Formulaire visiteurs en 3 étapes pour 5 bureaux d'information (Annot, Colmars-les-Alpes, Entrevaux, La Palud-sur-Verdon, Saint-André-les-Alpes), statistiques V3, export Excel | `/qualification`, `QualificationController`, `app/Livewire/Qualification*` |
| Brochures / Images | Gestion des brochures PDF (upload, compression Ghostscript, liens Calaméo), catégories / sous-catégories / auteurs / secteurs, menu public, statistiques de clics | `/admin/images`, `/admin/brochure-menu`, `/mes-brochures`, page publique `/brochures-oti-vt` |
| Commandes | Commande de brochures par les partenaires (Turnstile + honeypot) et gestion admin | `/commander-images`, `/admin/commandes` |
| Agendas | PDF d'agenda avec activation programmée | `/admin/agendas`, `/storage/agendas/agenda-en-cours.pdf` |
| Vérification des pages | Relecture des pages de verdontourisme.com (scan du sitemap, assignation aux relecteurs, multi-langues, revalidation annuelle) | `/verification`, `/verification/admin/*`, `app/Services/*Verification*`, `PageReleaseService`, `SitemapScanService` |
| Administration | Utilisateurs (approbation manuelle), rôles/permissions, export Apidae, soumissions de formulaires WordPress (CF7) | `/admin/users`, `/admin/apidae-export`, `/admin/contact-submissions` |

APIs : `GET /api/accommodations`, `GET /api/images[/{id}]` (public), `POST /api/contact-form/submit` (token `WORDPRESS_CF7_API_TOKEN`).

## Stack

- PHP 8.2+ (CI en 8.4), Laravel 12, Livewire 3, `livewire/flux` 2, `livewire/volt`
- Tailwind CSS 4 + DaisyUI 5 via Vite 6 (`resources/css/app.css`, `resources/js/app.js`)
- `spatie/laravel-permission`, `spatie/laravel-honeypot`, `coderflex/laravel-turnstile`, `maatwebsite/excel`, `intervention/image`, `stevebauman/purify`, `mailjet/mailjet-apiv3-php`
- Tests : Pest 3 ; style : Laravel Pint (preset par défaut, pas de `pint.json`)
- **Base de données de production : MySQL/MariaDB.** Queue, cache et sessions sur le driver `database`.

## Commandes

```bash
composer install && npm install
cp .env.example .env && php artisan key:generate
php artisan migrate --seed          # crée rôles/permissions + test@example.com / password (Super-admin)
composer dev                        # serve + queue:listen + vite en parallèle
npm run build                       # assets de prod
php artisan test                    # ou ./vendor/bin/pest ; filtrer : --filter=NomDuTest
vendor/bin/pint                     # formatage (lancé en CI)
php artisan schedule:list           # tâches planifiées
php artisan app:deploy-command      # clear caches + npm run build + config/route/view:cache
```

Commandes métier utiles : `apidae:fetch --all`, `emails:send-availability`, `accommodations:reset-status`,
`agendas:activate-pending`, `verification:revalidate-aged`, `verification:release-pages`,
`verification:purge`, `verification:restore-page`, `verification:recompute-statuses`,
`brochures:compress-existing`, `brochures:resync-sizes --dry-run`, `images:generate-json`
(voir `app/Console/Commands/` pour les signatures exactes et options).

## Pièges connus (à lire avant de toucher à la base ou aux tests)

1. **SQLite n'est plus supporté de bout en bout**, malgré le README et `.env.example` :
   - la migration `2026_05_22_100000_add_awaiting_validation_status_and_validated_at_to_verification_pages`
     utilise `ALTER TABLE … MODIFY COLUMN … ENUM` et `UPDATE … JOIN` (MySQL uniquement) → `migrate` échoue sur SQLite ;
   - `orderByRaw("FIELD(...)")` dans `PagesManager`, `PageReleaseService`, `VerificationReviewService`
     → `/dashboard` et `/verification` renvoient une 500 sur SQLite.
   Pour développer : utiliser MySQL/MariaDB. Toute nouvelle migration avec du SQL brut doit tester
   `Schema::getConnection()->getDriverName()` (modèle : `2025_12_10_164444_add_status_to_agendas_table.php`).
2. **Tests** : `.env.testing` et `phpunit.xml` forcent SQLite `:memory:` (pour ne jamais toucher la base de prod).
   À cause du point 1, la suite échoue actuellement (25/27 tests en échec à la migration).
   Ne jamais faire tourner les tests sur la base MySQL de production.
3. **CI** (`.github/workflows/`) ne se déclenche que sur `main`/`develop`, alors que la branche par défaut est
   `master` → la CI ne tourne pas en pratique. Elle nécessite aussi les secrets `FLUX_USERNAME` / `FLUX_LICENSE_KEY`.
4. **Approbation des comptes** : toute route protégée passe par le middleware `approved` (`users.approved = true`).
   Le seeder ne met pas `approved` à `true` pour `test@example.com` : il faut le faire à la main
   (voir `clearcache.md` ou `php artisan tinker`), sinon la connexion renvoie vers `/login`.
5. Les routes qualification sont contraintes par une regex de slugs de villes (dupliquée dans `routes/web.php`) :
   ajouter une ville = modifier toutes les occurrences + `QualificationController`.
6. Le planificateur est défini à **deux endroits** : `bootstrap/app.php` (`withSchedule` : Apidae 5h, reset statuts 3h,
   queue chaque minute, e-mails 6h) et `routes/console.php` (agendas 00:01, vérification 3h00 puis 3h30 — l'ordre compte).
   En prod, la queue est consommée par le cron (`queue:work --stop-when-empty`), pas par un worker permanent.

## Architecture & conventions

- **Logique métier dans `app/Services/`**, composants Livewire fins dans `app/Livewire/` (vues dans
  `resources/views/livewire/…`, en kebab-case). Traits partagés : `app/Livewire/Concerns/` (`WithSorting`, `EditsBrochures`).
- Tâches longues / e-mails → **Jobs** (`app/Jobs/`), queue `database`.
- Layouts Blade : `resources/views/components/layouts/` (app = sidebar Flux, auth, guest). Navigation : `components/layouts/app/sidebar.blade.php`
  — y ajouter les liens avec `@can('permission')`.
- **Autorisations** : middleware maison `permission:perm1,perm2` (OU logique, `app/Http/Middleware/CheckPermission.php`),
  policies dans `app/Policies/`, rôles/permissions définis dans `database/seeders/RolePermissionSeeder.php`
  (+ seeders `Add*PermissionSeeder` pour les permissions ajoutées ensuite, à lancer en prod avec `db:seed --class=…`).
  Rôles : Super-admin, Admin, Qualification, Disponibilites, Utilisateurs.
- Toujours une migration pour les changements de schéma ; soft deletes sur `images` et `verification_pages`.
- Échapper les sorties (`{{ }}`) ; HTML utilisateur → `Purify`. Valider côté serveur.
- Liens envoyés aux hébergeurs = **URLs signées** (`signed`), page d'expiration gérée dans `bootstrap/app.php`.
- Les 404 sur des `.pdf` sont journalisées dans `pdf_not_found_logs`.
- Messages de commit courts en français, branches `feature/<sujet>` puis PR vers `master`.

## Variables d'environnement spécifiques

`APIDAE_API_KEY`, `APIDAE_PROJECT_ID`, `APIDAE_SELECTION_ID`, `MAILJET_APIKEY`, `MAILJET_APISECRET`,
`ORDER_NOTIFICATION_EMAIL`, `TURNSTILE_SITE_KEY`, `TURNSTILE_SECRET_KEY`, `WORDPRESS_CF7_API_TOKEN`,
`GHOSTSCRIPT_BIN`, `PDF_COMPRESSION_ENABLED`, `VERIFICATION_SITEMAP_URL`, `HONEYPOT_*`.
Sans clés Apidae/Mailjet, l'interface fonctionne mais import et e-mails échouent (mettre `MAIL_MAILER=log` en local).

## Documentation existante

Détails fonctionnels dans les fichiers à la racine (certains passages sont datés — le code fait foi) :
`ANALYSE_APPLICATION.md` (vue d'ensemble), `APIDAE_SETUP.md`, `APIDAE_SCHEDULING.md`, `MAILJET_SETUP.md`,
`QUEUES_JOBS_ET_FICHIERS.md`, `DOCUMENTATION_STATISTIQUES.md`, `PLAN_UPLOAD_IMAGES.md`, `SECURITY_SETUP.md`,
`Outil-qualification.md`, `clearcache.md`, ainsi que `.claude/instructions.md` et `.claude/project_context.md`.

## Fichiers à ne pas committer / à manipuler avec précaution

Les CSV à la racine (`old-data-qualification.csv`, `data-email-mailjet.csv`, `3AF1C16098D546739570CBC86377D28B.csv`,
`regroupement.csv`) contiennent des données réelles (e-mails, réponses visiteurs) : ne pas les modifier ni les diffuser.
Le fichier `first())` à la racine est un artefact accidentel.
