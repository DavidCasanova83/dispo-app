# Brochures : versions anglaise et italienne

Une brochure du catalogue (`/brochures-oti-vt`) est en français ; sa version
anglaise et sa version italienne lui sont **rattachées** au lieu d'être des
brochures à part (« English - Hôtels »). Une version EN et une version IT au
plus par brochure, jamais de version sans brochure française.

## Données

Table `images` (migration `2026_11_04_100000_add_language_versions_to_images_table`) :

| Colonne | Rôle |
|---|---|
| `language` | `fr` pour une brochure, `en` ou `it` pour une version |
| `version_of_id` | brochure française dont le document est la version (`null` pour une brochure) |
| `extra_languages` | autres langues contenues dans le même document français (carte bilingue FR/EN → `["en"]`) |
| `slug` | adresse permanente `/brochure/{slug}` (brochures seulement ; repris du nom du PDF à la migration) |

Une version est un document comme un autre (fichier, couverture, titre, lien
Calaméo, année d'édition, stock, clics, hors ligne), mais elle **reprend le
classement de sa brochure** : catégorie, sous-catégorie, secteur, auteur,
responsable (copiés à l'enregistrement et suivis quand la brochure change,
`Image::booted()`). Supprimer une brochure supprime ses versions.

Code : `App\Models\Image` (`versions()`, `editions()`, `scopeMain()`,
`scopeAvailableIn()`, `permalink()`), `App\Services\BrochureVersionService`
(ajouter, rattacher, supprimer), `App\Services\BrochureLanguageMatcher`
(reprise des anciens titres).

## Catalogue public

- **Une carte par brochure.** Si elle existe en plusieurs langues, des
  pastilles **FR · EN · IT** sous le titre changent le document affiché :
  couverture, titre, description, année, et les boutons Consulter /
  Télécharger / Copier le lien ouvrent la langue choisie. Les clics sont
  comptés sur le document de la langue.
- **Filtre « Langue »** (barre des filtres) : *Toutes langues*, *English (n)*,
  *Italiano (n)*. Il ne garde que les brochures lisibles dans la langue (une
  version, ou un document français qui la contient) et ouvre leurs cartes
  dans cette langue. Adresse : `/brochures-oti-vt?langue=en`,
  `/brochures-oti-vt/guides-dhebergements?langue=it`…
- La recherche trouve aussi le titre et la description des versions.
- Visiteurs : versions hors ligne masquées (comme les brochures).
- Connectés : point orange sur une pastille quand la version est plus
  ancienne que la brochure française.

## Adresse permanente

`/brochure/{slug}/{langue}` (`BrochurePermalinkController`) redirige vers le
document actuel de la langue (PDF, sinon Calaméo, sinon le lien), ou vers le
français s'il n'y a pas de version dans la langue. Elle ne change pas quand
le PDF est remplacé.

- `/brochure/visites-guidees-pour-les-groupes` → PDF français
- `/brochure/visites-guidees-pour-les-groupes/en` → PDF anglais, sinon français
- Le bouton **Copier le lien** des cartes copie cette adresse, dans la langue affichée.
- À mettre dans les pages EN / IT de verdontourisme.com à la place des
  `…/storage/pdfs/….pdf` français : elles basculeront seules sur la
  traduction le jour où elle est ajoutée.
- Brochure hors ligne ou d'un auteur réservé : 404 pour les visiteurs.

## Gestion

- **Fenêtre de modification** (catalogue et `/admin/images`), partie
  **Versions linguistiques** :
  - « Ce document contient aussi : l'anglais / l'italien » (document bilingue) ;
  - par langue : **Ajouter** (titre, année, PDF ou image et/ou lien Calaméo,
    couverture facultative — sinon copie de celle de la brochure), ou
    **Rattacher** un document déjà en ligne (il garde fichier et statistiques) ;
  - version existante : **Modifier** (la fenêtre s'ouvre sur la version, sans
    les champs de classement ; « Revenir à la brochure française »),
    **Supprimer** (fichiers effacés).
- **/admin/images** : une ligne par brochure, pastilles `EN 2025` / `IT 2026`
  (orange si plus ancienne que le français) qui ouvrent la version.
- **Mes brochures** : mêmes pastilles ; le responsable modifie titre,
  couverture, PDF et année de chaque version.
- **Statistiques** : pastille EN / IT dans le classement, ligne « Par langue ».
- **API** `/api/images` et `storage/api/images.json` : champs `language`,
  `version_of_id`, `permalink` (les versions restent listées).

## Mise en production

```bash
php artisan migrate
php artisan app:deploy-command                   # AVANT la reprise : routes (cache), assets
php artisan brochures:lier-versions              # aperçu : rien n'est modifié
php artisan brochures:lier-versions --appliquer
```

Le cache des routes doit connaître `/brochure/{slug}/{langue}` avant la reprise
(`app:deploy-command` ou `route:clear`) : sinon la commande s'arrête sans rien
modifier. Avant ce garde-fou (4/11/2026), elle enregistrait les rattachements
puis échouait en refaisant `images.json` (« Route [brochure.permalink] not
defined ») : il suffit alors de lancer `app:deploy-command` puis de relancer
`brochures:lier-versions --appliquer`, qui ne refait rien de ce qui est déjà
rattaché, refait le JSON et signale les sous-catégories vidées.

La commande lit la langue dans les titres (« English - … », « English
Version - … », « … IT », « … (EN) », nom du PDF `english-…` / `…-it`),
propose la brochure française la plus proche (mots du titre, même catégorie)
et, avec `--appliquer`, rattache les versions, retire la mention de langue du
titre (« English - Hôtels » → « Hôtels », à traduire ensuite si besoin) et
marque les documents « … FR/EN » comme bilingues.

Sur les données du 4/11/2026 (52 brochures en ligne), elle propose :

- les 13 « English - … » des guides d'hébergement → leur guide français ;
- « Carte Touristique - Verdon Tourisme IT » → version italienne de la carte,
  et la carte « FR/EN » devient bilingue ;
- **doublon** : « English - Saint-André-les-Alpes et alentours » existe deux
  fois (n° 123 et 124) ; la plus récente est rattachée, l'autre est signalée
  (à supprimer) ;
- **à rattacher à la main** : « English Version - Magazine Sources d'Envies »
  (aucun titre français proche) : depuis la fenêtre de la brochure française
  du magazine (« Ou rattacher un document déjà en ligne »), ou
  `--paire=id_document:id_brochure`.

Ensuite :

1. **Menu des brochures** : l'entrée « English version » pointait vers la
   sous-catégorie `guides-dhebergements/english-version`, vidée par la
   reprise (les versions prennent le classement de leur brochure) : la faire
   pointer vers `/brochures-oti-vt/guides-dhebergements?langue=en` (ou
   `/brochures-oti-vt?langue=en`), puis supprimer la sous-catégorie. La
   commande signale les sous-catégories vidées et les menus qui y mènent.
2. Vérifier les titres des versions et les traduire si besoin.
3. Remplacer, dans les pages EN / IT de verdontourisme.com, les liens vers
   les PDF français par les adresses permanentes `/brochure/{slug}/en` et
   `/it` (une fois ce déploiement en ligne, sinon 404).

Tests : `tests/Feature/BrochureVersionsTest.php`.
