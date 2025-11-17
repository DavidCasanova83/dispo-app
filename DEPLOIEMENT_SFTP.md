# Déploiement de la Fonctionnalité SFTP Upload

## 📋 Résumé de la fonctionnalité

Cette fonctionnalité permet aux **Super-Admin** d'uploader des fichiers PDF vers un serveur SFTP configuré. Elle inclut :

- **Configuration SFTP** : Interface d'administration pour configurer la connexion au serveur SFTP
- **Upload de fichiers PDF** : Upload sécurisé de fichiers PDF uniquement (max 50 Mo)
- **Historique des uploads** : Traçabilité complète de tous les uploads avec statut de réussite/échec
- **Permissions** : Nouvelle permission `sftp-upload` réservée exclusivement aux Super-Admin

## 🆕 Fichiers créés

### Migrations
- `database/migrations/2025_01_17_000001_create_sftp_configurations_table.php`
- `database/migrations/2025_01_17_000002_create_sftp_uploads_table.php`

### Modèles
- `app/Models/SftpConfiguration.php`
- `app/Models/SftpUpload.php`

### Services
- `app/Services/SftpService.php`

### Composants Livewire
- `app/Livewire/Sftp/Configuration.php`
- `app/Livewire/Sftp/Upload.php`
- `app/Livewire/Sftp/History.php`

### Vues Blade
- `resources/views/livewire/sftp/configuration.blade.php`
- `resources/views/livewire/sftp/upload.blade.php`
- `resources/views/livewire/sftp/history.blade.php`

## ✏️ Fichiers modifiés

### Seeders
- `database/seeders/RolePermissionSeeder.php`
  - Ajout de la permission `sftp-upload`
  - Cette permission est automatiquement attribuée aux Super-Admin

### Routes
- `routes/web.php`
  - Ajout des routes SFTP protégées par la permission `sftp-upload`

## 📦 Dépendances requises

### Installation de la bibliothèque SFTP

La fonctionnalité nécessite la bibliothèque **phpseclib** pour la connexion SFTP :

```bash
composer require phpseclib/phpseclib:~3.0
```

Cette bibliothèque permet :
- Connexion SSH/SFTP sécurisée
- Support de l'authentification par mot de passe et clé privée
- Upload de fichiers via le protocole SFTP

## 🚀 Instructions de déploiement

### 1. Récupérer les dernières modifications

```bash
git pull origin claude/super-admin-feature-01WntYQccaBgQouey3wGfgwX
```

### 2. Installer les dépendances

```bash
composer install
```

### 3. Installer la bibliothèque SFTP

```bash
composer require phpseclib/phpseclib:~3.0
```

### 4. Exécuter les migrations

```bash
php artisan migrate
```

Cela créera les tables suivantes :
- `sftp_configurations` : Stocke les configurations SFTP
- `sftp_uploads` : Historique des uploads avec statut

### 5. Mettre à jour les permissions

#### Option A : Base de données vide (fresh install)

```bash
php artisan migrate:fresh --seed
```

⚠️ **ATTENTION** : Cette commande supprime toutes les données existantes !

#### Option B : Base de données avec données existantes

Si votre base de données contient déjà des utilisateurs et des données, exécutez uniquement le seeder de permissions :

```bash
php artisan db:seed --class=RolePermissionSeeder
```

⚠️ **Note** : Si les rôles et permissions existent déjà, vous devrez soit :
- Créer manuellement la permission `sftp-upload` dans la base de données
- Ou modifier le seeder pour vérifier l'existence avant la création

**Création manuelle de la permission :**

```sql
INSERT INTO permissions (name, guard_name, created_at, updated_at)
VALUES ('sftp-upload', 'web', NOW(), NOW());

-- Attribuer la permission aux Super-Admin
INSERT INTO role_has_permissions (permission_id, role_id)
SELECT p.id, r.id
FROM permissions p, roles r
WHERE p.name = 'sftp-upload' AND r.name = 'Super-admin';
```

### 6. Vider le cache

```bash
php artisan cache:clear
php artisan config:clear
php artisan route:clear
php artisan view:clear
```

Important pour Spatie Permission :

```bash
php artisan permission:cache-reset
```

### 7. Optimiser l'application

```bash
php artisan optimize
php artisan config:cache
php artisan route:cache
php artisan view:cache
```

## 🔧 Configuration du serveur SFTP

### Accès à la configuration

1. Connectez-vous avec un compte **Super-Admin**
2. Accédez à `/sftp/configuration`
3. Remplissez les informations suivantes :

| Champ | Description | Exemple |
|-------|-------------|---------|
| **Nom** | Nom de la configuration | "Serveur SFTP Production" |
| **Hôte** | Adresse du serveur SFTP | "sftp.example.com" ou "192.168.1.100" |
| **Port** | Port SFTP (généralement 22) | 22 |
| **Nom d'utilisateur** | Identifiant SFTP | "user_sftp" |
| **Mot de passe** | Mot de passe SFTP (crypté en base) | "********" |
| **Chemin distant** | Dossier de destination sur le serveur | "/uploads/pdf" ou "/" |
| **Timeout** | Délai de connexion en secondes | 30 |
| **Configuration active** | Activer cette configuration | ✓ Coché |

### Tester la connexion

Avant d'enregistrer, utilisez le bouton **"Tester la connexion"** pour vérifier :
- La connexion au serveur SFTP
- L'authentification
- L'accès au répertoire distant

### Sécurité

- Le **mot de passe** est automatiquement crypté avec `Crypt::encryptString()`
- Les informations sensibles ne sont jamais affichées dans les logs
- Une seule configuration peut être active à la fois

## 📤 Utilisation de l'upload

### Accès

Route : `/sftp/upload`

### Restrictions

- **Format** : Seuls les fichiers PDF sont acceptés (`.pdf`, `application/pdf`)
- **Taille maximale** : 50 Mo
- **Permission** : Réservé aux utilisateurs ayant la permission `sftp-upload` (Super-Admin uniquement)

### Processus d'upload

1. Sélectionner un fichier PDF
2. Le fichier est validé (format + taille)
3. Cliquer sur "Uploader le fichier"
4. Le fichier est transféré vers le serveur SFTP
5. Un enregistrement est créé dans l'historique

### Nom du fichier sur le serveur

Le fichier est automatiquement renommé pour éviter les conflits :
```
Format : nom-du-fichier_timestamp.pdf
Exemple : rapport-annuel_1705483920.pdf
```

## 📊 Historique des uploads

### Accès

Route : `/sftp/history`

### Fonctionnalités

- **Liste complète** : Tous les uploads avec détails
- **Recherche** : Par nom de fichier ou utilisateur
- **Filtres** : Par statut (Réussis / Échoués / Tous)
- **Pagination** : 15 uploads par page
- **Informations affichées** :
  - Nom du fichier original
  - Chemin sur le serveur SFTP
  - Utilisateur ayant effectué l'upload
  - Taille du fichier
  - Statut (Réussi / Échoué)
  - Date et heure
  - Message d'erreur (si échec)

## ✅ Tests à effectuer

### 1. Test des permissions

```bash
# En tant que Super-Admin
✓ Accès à /sftp/configuration
✓ Accès à /sftp/upload
✓ Accès à /sftp/history

# En tant qu'Admin (ou autre rôle)
✗ Accès refusé (403) à toutes les routes SFTP
```

### 2. Test de la configuration

- [ ] Créer une configuration SFTP avec des identifiants valides
- [ ] Tester la connexion (doit réussir)
- [ ] Créer une configuration avec des identifiants invalides
- [ ] Tester la connexion (doit échouer)
- [ ] Modifier une configuration existante
- [ ] Vérifier que le mot de passe n'est pas affiché après sauvegarde

### 3. Test de l'upload

- [ ] Uploader un fichier PDF valide (< 50 Mo)
- [ ] Vérifier que le fichier apparaît dans l'historique avec statut "Réussi"
- [ ] Vérifier que le fichier existe sur le serveur SFTP
- [ ] Tenter d'uploader un fichier non-PDF (doit échouer)
- [ ] Tenter d'uploader un PDF > 50 Mo (doit échouer)
- [ ] Uploader sans configuration active (doit afficher un avertissement)

### 4. Test de l'historique

- [ ] Vérifier que tous les uploads apparaissent
- [ ] Tester la recherche par nom de fichier
- [ ] Tester le filtre par statut
- [ ] Vérifier la pagination
- [ ] Vérifier que les erreurs sont bien enregistrées en cas d'échec

### 5. Test de sécurité

- [ ] Se connecter avec un compte non Super-Admin
- [ ] Vérifier l'accès refusé (403) aux routes SFTP
- [ ] Vérifier que le mot de passe est bien crypté dans la base de données
- [ ] Vérifier que les informations sensibles n'apparaissent pas dans les logs

## 🔐 Sécurité et bonnes pratiques

### Cryptage des données sensibles

- Les **mots de passe SFTP** sont cryptés avec `Laravel Crypt`
- Les **clés privées SSH** (si utilisées) sont également cryptées
- Aucune donnée sensible n'est loggée

### Validation des fichiers

- Extension validée : `.pdf`
- MIME type validé : `application/pdf`
- Taille maximale : 50 Mo
- Nom de fichier sanitisé avant upload

### Logs

Tous les événements importants sont loggés :
- Échecs de connexion SFTP
- Échecs d'upload
- Erreurs de configuration

Vérifier les logs Laravel :
```bash
tail -f storage/logs/laravel.log
```

## 🗄️ Structure de la base de données

### Table : `sftp_configurations`

| Colonne | Type | Description |
|---------|------|-------------|
| id | bigint | Clé primaire |
| name | varchar(255) | Nom de la configuration |
| host | varchar(255) | Hôte SFTP |
| port | int | Port SFTP (défaut: 22) |
| username | varchar(255) | Nom d'utilisateur |
| password | text | Mot de passe (crypté) |
| private_key | text | Clé privée SSH (optionnel, crypté) |
| remote_path | varchar(500) | Chemin distant |
| timeout | int | Timeout en secondes (défaut: 30) |
| is_active | boolean | Configuration active |
| created_at | timestamp | Date de création |
| updated_at | timestamp | Date de modification |

### Table : `sftp_uploads`

| Colonne | Type | Description |
|---------|------|-------------|
| id | bigint | Clé primaire |
| user_id | bigint | ID utilisateur (FK) |
| sftp_configuration_id | bigint | ID configuration (FK) |
| original_filename | varchar(255) | Nom original du fichier |
| remote_filename | varchar(255) | Nom sur le serveur |
| remote_path | varchar(500) | Chemin complet |
| file_size | bigint | Taille en octets |
| status | varchar(255) | success / failed |
| error_message | text | Message d'erreur (nullable) |
| created_at | timestamp | Date d'upload |
| updated_at | timestamp | Date de modification |

## 🐛 Dépannage

### Erreur : "Class 'phpseclib3\Net\SFTP' not found"

```bash
composer require phpseclib/phpseclib:~3.0
composer dump-autoload
```

### Erreur : "Permission denied" lors de la connexion SFTP

- Vérifier les identifiants SFTP
- Vérifier que l'utilisateur a les droits d'accès au dossier distant
- Vérifier le pare-feu / règles de sécurité réseau

### Erreur : "Could not change to remote directory"

Le dossier distant n'existe pas. Options :
1. Créer manuellement le dossier sur le serveur SFTP
2. Le service tentera de le créer automatiquement si les permissions le permettent

### Erreur : "Class 'Flux' not found"

Vérifier que Flux UI est bien installé :
```bash
composer require livewire/flux
```

### Permission refusée (403)

- Vérifier que l'utilisateur a le rôle "Super-admin"
- Vider le cache des permissions :
```bash
php artisan permission:cache-reset
```

## 📞 Support

En cas de problème lors du déploiement :

1. Vérifier les logs Laravel : `storage/logs/laravel.log`
2. Vérifier les logs du serveur web (Nginx/Apache)
3. Vérifier la connexion au serveur SFTP manuellement :
```bash
sftp -P 22 username@host
```

## 🎯 Routes disponibles

| Route | Nom | Permission | Description |
|-------|-----|------------|-------------|
| `/sftp/configuration` | sftp.configuration | sftp-upload | Configuration SFTP |
| `/sftp/upload` | sftp.upload | sftp-upload | Upload de fichiers PDF |
| `/sftp/history` | sftp.history | sftp-upload | Historique des uploads |

## ✨ Fonctionnalités futures (optionnelles)

- Support de l'authentification par clé SSH (privée/publique)
- Upload de plusieurs fichiers simultanément
- Prévisualisation des PDF avant upload
- Export de l'historique en Excel
- Notifications par email après upload réussi
- Gestion de plusieurs configurations SFTP actives
- Planification d'uploads automatiques

---

**Date de création** : 17 janvier 2025
**Version** : 1.0
**Auteur** : Claude AI
**Framework** : Laravel 12 + Livewire 3 + Flux UI
