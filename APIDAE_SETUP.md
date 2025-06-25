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
# Test avec des données de test
php artisan apidae:fetch --test

# Test avec l'API réelle (limite de 10 hébergements)
php artisan apidae:fetch --limit=10

# Test avec l'API réelle (limite de 50 hébergements par défaut)
php artisan apidae:fetch
```

## 7. Paramètres de la commande

-   `--test` : Utilise des données de test au lieu de l'API
-   `--limit=100` : Limite le nombre d'hébergements récupérés (défaut: 50)

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

-   Vérifiez les critères de votre sélection
-   Essayez d'augmenter la limite avec `--limit=100`

## 10. Visualisation

Après avoir récupéré les données, vous pouvez les visualiser :

1. Connectez-vous à votre application
2. Allez sur le dashboard
3. Cliquez sur "Hébergements" (icône 🏨)
