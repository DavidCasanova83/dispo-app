# Corrections confiées à Claude

Deux écrans du panel peuvent confier une correction à Claude, sur le même
principe (issue GitHub `@claude` dans le dépôt du site, PR créée par le panel
quand Claude a fini) :

- les **traductions** EN/IT signalées par les traducteurs (ci-dessous) ;
- les **pages** à partir des retours des relecteurs (section « Corrections de
  page » plus bas).

Le suivi GitHub (ligne repère, PR, fermeture de l'issue, délai) est commun :
`app/Services/Concerns/FollowsClaudeIssues.php`, états dans
`app/Models/Concerns/HasClaudeRequestStatus.php`.

## Corrections de traduction

Dans **Vérification > Suivi des traductions** (`/verification/admin/traductions`),
une traduction EN ou IT signalée « à corriger » par un traducteur peut être
envoyée à Claude, comme le bouton « Demander une modification » et « Lancer
les modifications » de l'admin WordPress du site.

### Fonctionnement

1. **Fiche de la page > bloc « 🤖 Correction par Claude »** de la langue :
   précisions facultatives, puis **Demander la correction à Claude**.
2. Le panel crée une **issue GitHub** `@claude` dans le dépôt du site
   (`DavidCasanova83/VT-custom-2025`), titre `[Traduction EN] <page> — …`,
   avec : la page, la langue, les adresses EN/IT/FR, la date du signalement,
   le **commentaire du traducteur** (cité tel quel), la réponse déjà faite au
   traducteur s'il y en a une, les précisions de l'admin et les consignes.
   La traduction passe **« En cours »** (le traducteur ne peut plus modifier
   son verdict).
3. **Claude Code** (workflow `.github/workflows/claude.yml` du dépôt du site)
   suit `themes/verdon-site/docs/traductions-claude.md` : il corrige le
   fichier `contenu.<langue>.md` de la page sur sa branche, répond dans
   l'issue et termine par la ligne repère
   `VERDON-TRADUCTION-CLAUDE demande=<référence> branche=<branche>`.
4. **Toutes les 5 minutes** (`translations:claude-sync`, `routes/console.php`)
   ou sur **« Vérifier maintenant »** : quand la ligne repère est là, le panel
   **crée la PR** (branche de Claude → branche par défaut), ferme l'issue, puis
   suit la PR : **PR ouverte → PR fusionnée** (ou fermée sans fusion).
5. Après fusion et déploiement du site, vérifier la page en ligne, puis
   **Marquer corrigée** : la traduction passe « Corrigée » pour le traducteur.
   Le panel ne le fait jamais seul.

États d'une demande (`TranslationClaudeRequest::STATUSES`) : **Chez Claude**,
**PR ouverte**, **PR fusionnée**, **PR fermée sans fusion**, **Aucune
modification** (page de l'admin WordPress, sans `.md` : Claude donne dans
l'issue le texte corrigé à coller à la main ; ou correction non applicable),
**Échec** (erreur de Claude, ou pas de fin après 3 heures : « Renvoyer à
Claude »). Une seule demande en cours par traduction ; l'historique est gardé
(table `translation_claude_requests`).

Code : `app/Services/TranslationClaudeService.php` (issue, suivi, PR),
`app/Services/GitHubService.php` (API GitHub, erreurs expliquées),
`app/Console/Commands/SyncTranslationClaudeRequests.php`,
`app/Livewire/Verification/Admin/TranslationsManager.php` (`askClaude`,
`refreshClaude`), tests `tests/Feature/Verification/TranslationClaudeTest.php`.

## Corrections de page (retours des relecteurs)

Dans **Vérification des pages**, fiche d'une page
(`/verification/admin/pages/{id}`), bloc **« 🤖 Correction par Claude »** :
réservé au **super-admin** (gate `ask-claude-page-correction`, définie dans
`AppServiceProvider` : rôle Super-admin et permission
`manage-page-verification`). Un autre rôle qui recevrait la vérification des
pages ne voit pas le bloc et ne peut pas l'utiliser.

1. Le bloc liste les **retours signalés et pas encore réglés** du cycle en
   cours (« Nouveau » ou « En correction »), tous cochés ; l'admin décoche ceux
   à ne pas transmettre, ajoute des précisions s'il le souhaite, puis
   **Demander la correction à Claude**.
2. Le panel crée une issue `[Vérification] <page> — retours des relecteurs`
   avec : la page, ses adresses FR/EN/IT, la catégorie, chaque retour cité
   (problème de contenu, médias / liens, remarques, à ajouter, à supprimer,
   question sans réponse, suggestions, réponse déjà faite au relecteur), les
   précisions et les consignes. **Les noms des relecteurs ne sont pas
   transmis.** Les retours « Nouveau » passent **« En correction »** (le
   relecteur le voit) et l'envoi est inscrit au journal de la page.
3. Claude suit `themes/verdon-site/docs/verification-claude.md` du dépôt du
   site : il corrige `contenu.fr.md` de la page, reporte les corrections de
   fond dans `contenu.en.md` et `contenu.it.md`, répond dans l'issue et termine
   par `VERDON-VERIFICATION-CLAUDE demande=<référence> branche=<branche>`.
   Page de l'admin WordPress (sans `.md`) : il ne modifie rien et donne la
   marche à suivre dans l'issue (`branche=aucune`).
4. **Toutes les 5 minutes** (`verification:claude-sync`) ou sur **« Vérifier
   maintenant »** : PR créée, issue fermée, puis PR suivie (ouverte,
   fusionnée, fermée). Chaque étape est inscrite au journal de la page.
5. Après fusion et déploiement : vérifier la page en ligne, puis marquer
   chaque retour **Réglé** dans la fiche, et clôturer la page quand tout est
   réglé. Le panel ne le fait jamais seul.

Mêmes états que pour les traductions ; une seule demande en cours par page ;
historique gardé (table `verification_claude_requests`, avec le texte des
retours envoyés). Les cartes de l'onglet **À traiter** signalent une demande
en cours (« 🤖 Claude : … »).

Code : `app/Services/VerificationClaudeService.php`,
`app/Console/Commands/SyncVerificationClaudeRequests.php`,
`app/Livewire/Verification/Admin/PageSheet.php` (`askClaude`,
`refreshClaude`), tests `tests/Feature/Verification/PageClaudeTest.php`.

## Mise en place (une fois)

### 1. Claude sur le dépôt du site

Déjà fait pour l'admin WordPress (`themes/verdon-site/docs/demandes-claude.md`
du dépôt du site) : application GitHub **Claude** installée sur
`VT-custom-2025`, secret `CLAUDE_CODE_OAUTH_TOKEN`, workflow `claude.yml`.
Les méthodes `themes/verdon-site/docs/traductions-claude.md` et
`themes/verdon-site/docs/verification-claude.md` doivent être sur la branche
par défaut du dépôt du site (fusion des PR qui les ajoutent).

### 2. Jeton GitHub pour le panel

GitHub > Settings (du compte) > Developer settings > Personal access tokens >
**Fine-grained tokens** > *Generate new token* :

- **Repository access** : *Only select repositories* → `VT-custom-2025` ;
- **Permissions** : *Issues* → **Read and write**, *Pull requests* → **Read
  and write**, *Contents* → **Read-only** (branche de Claude et comparaison) ;
  *Metadata* → Read, ajouté d'office ; rien d'autre ;
- une date d'expiration (à renouveler).

Le jeton de l'admin WordPress (`VERDON_GITHUB_TOKEN`) a déjà ces droits et
peut être repris, mais un jeton à part se révoque sans couper l'autre.
Les issues sont créées **au nom du compte du jeton** : il doit avoir les droits
d'écriture sur le dépôt, sinon Claude ne répond pas.

### 3. `.env` de production

```dotenv
CLAUDE_GITHUB_TOKEN=github_pat_…
# facultatif (valeur par défaut) :
CLAUDE_GITHUB_REPOSITORY=DavidCasanova83/VT-custom-2025
```

Puis `php artisan migrate` (tables `translation_claude_requests` et
`verification_claude_requests`) et
`php artisan config:cache`. Le suivi passe par le planificateur déjà lancé
chaque minute par le cron (`schedule:run`).

### 4. Vérifier

```bash
php artisan translations:claude-sync --test   # lecture seule : dépôt, privé ou public
```

Puis envoyer une demande sur une traduction signalée : l'issue apparaît dans
GitHub (onglet Issues), Claude y répond (onglet Actions, workflow « Claude »),
et la PR est créée au plus 5 minutes après sa ligne repère.

## Bon à savoir

- **Visibilité** : l'issue est visible de toutes les personnes qui ont accès
  au dépôt du site. Le nom du traducteur ou des relecteurs n'y figure pas ;
  ne pas mettre de donnée personnelle dans les précisions. Les retours des
  relecteurs sont cités tels quels : relire avant d'envoyer si l'un d'eux
  contient une information personnelle (le décocher au besoin).
- **Erreurs expliquées** : jeton refusé ou expiré (401), droits insuffisants
  (403), dépôt introuvable (404). Une erreur pendant le suivi (réseau, GitHub
  indisponible) s'affiche sous la demande, qui reste suivie.
- **Coût** : chaque demande consomme le quota de l'abonnement Claude (ou des
  crédits API) du dépôt du site.
