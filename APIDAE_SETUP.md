# Configuration API Apidae

## 1. Créer un compte Apidae

1. Allez sur [https://dev.apidae-tourisme.com/](https://dev.apidae-tourisme.com/)
2. Créez un compte développeur
3. Connectez-vous à votre espace développeur

## 2. Créer un projet

1. Dans votre espace développeur, créez un nouveau projet
2. Notez votre **Project ID** (ex: `12345`)

## 3. Créer une sélection

1. Dans votre projet, créez une nouvelle sélection
2. Configurez les critères selon vos besoins :
    - Type d'objet : Hébergement
    - Localisation : Votre région
    - Autres critères selon vos besoins
3. Notez votre **Selection ID** (ex: `67890`)

## 4. Obtenir votre clé API

1. Dans les paramètres de votre projet, trouvez votre **API Key**
2. Notez cette clé (ex: `abc123def456ghi789`)

## 5. Configurer votre application

Créez un fichier `.env` à la racine de votre projet avec ces variables :

```env
# Configuration API Apidae
APIDAE_API_KEY=votre_clé_api_ici
APIDAE_PROJECT_ID=votre_project_id_ici
APIDAE_SELECTION_ID=votre_selection_id_ici
```

## 6. Tester la configuration

```bash
# Test avec des données de test (5 hébergements fictifs)
php artisan apidae:fetch --test

# Test avec l'API réelle (limite de 10 hébergements)
php artisan apidae:fetch --limit=10

# Récupération avec limite par défaut (150 hébergements maximum)
php artisan apidae:fetch

# Récupération de TOUS les hébergements disponibles (recommandé)
php artisan apidae:fetch --all
```

## 7. Paramètres de la commande

### Options disponibles

-   `--test` : Utilise des données de test au lieu de l'API (5 hébergements fictifs)
-   `--all` : **Récupère automatiquement TOUS les hébergements disponibles** (pagination automatique)
-   `--limit=N` : Limite le nombre d'hébergements récupérés (défaut: 150)
-   `--simple` : Utilise une requête simple sans critères de filtrage

### Pagination automatique

La commande gère automatiquement la pagination de l'API Apidae :
- L'API retourne **20 hébergements maximum par requête**
- La commande effectue automatiquement plusieurs requêtes pour récupérer tous les hébergements
- Affichage de la progression en temps réel (ex: "Page 2/12, hébergements 21-40/225")
- Pause de 100ms entre chaque requête pour ne pas surcharger l'API

### Exemples d'utilisation

```bash
# Récupérer TOUS les hébergements (pagination automatique)
php artisan apidae:fetch --all

# Récupérer exactement 50 hébergements
php artisan apidae:fetch --limit=50

# Récupérer tous les hébergements en mode simple
php artisan apidae:fetch --all --simple

# Test sans appel API
php artisan apidae:fetch --test
```

### Exemple de sortie

```
Récupération des hébergements depuis Apidae…
Mode: Récupération de TOUS les hébergements disponibles
Configuration utilisée :
  - Project ID: 7088
  - Selection ID: 142158
  - Mode simple: Non

Récupération du nombre total d'hébergements...
✓ 225 hébergements disponibles au total
Récupération de 225 hébergements en 12 page(s)...

→ Page 2/12 (hébergements 21-40/225)
→ Page 3/12 (hébergements 41-60/225)
...
→ Page 12/12 (hébergements 221-225/225)

✓ 225 hébergements récupérés au total

✅ Opération terminée avec succès !
   - Hébergements créés : 25
   - Hébergements mis à jour : 200
   - Total traité : 225
```

## 8. Champs récupérés

La commande récupère automatiquement :

-   Nom de l'hébergement
-   Ville
-   Email (si disponible)
-   Téléphone (si disponible)
-   Site web (si disponible)
-   Description (si disponible)
-   Type d'hébergement
-   Identifiant Apidae

## 9. Dépannage

### Erreur 404

-   Vérifiez que votre Project ID et Selection ID sont corrects
-   Assurez-vous que votre sélection contient des hébergements

### Erreur d'authentification

-   Vérifiez que votre API Key est correcte
-   Assurez-vous que votre projet est actif

### Aucun résultat

-   Vérifiez les critères de votre sélection dans votre espace Apidae
-   Utilisez `--all` pour récupérer tous les hébergements disponibles
-   Vérifiez le nombre total d'hébergements dans la réponse de l'API

### Récupération partielle

-   Si la commande s'arrête en cours de pagination, elle traite quand même les hébergements déjà récupérés
-   Vérifiez votre connexion internet
-   Relancez la commande avec `--all` pour récupérer les hébergements manquants

## 10. Visualisation

Après avoir récupéré les données, vous pouvez les visualiser :

1. Connectez-vous à votre application
2. Allez sur le dashboard
3. Cliquez sur "Hébergements" (icône 🏨)
