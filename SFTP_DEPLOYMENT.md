# Guide de déploiement - Fonctionnalité Upload PDF vers SFTP

## Vue d'ensemble

Cette fonctionnalité permet aux utilisateurs autorisés d'uploader des fichiers PDF vers un serveur SFTP configuré. Les Super-Admin peuvent configurer les connexions SFTP, tandis que les Admin (avec permission) peuvent uploader des fichiers.

## Architecture

### Composants principaux

1. **Permissions** :
   - `manage-sftp-config` : Configuration SFTP (Super-Admin uniquement)
   - `upload-sftp-pdf` : Upload de fichiers PDF (Admin et Super-Admin)

2. **Base de données** :
   - `sftp_configurations` : Stockage des configurations SFTP
   - `sftp_uploads` : Historique et suivi des uploads

3. **Services** :
   - `SftpService` : Gestion des connexions et transferts SFTP
   - `ProcessSftpUpload` : Job asynchrone pour les uploads

4. **Composants Livewire** :
   - `Admin/SftpConfiguration` : Gestion des configurations (Super-Admin)
   - `Admin/SftpUpload` : Interface d'upload (Admin)

---

## Instructions de déploiement

### Étape 1 : Vérifier les dépendances

La dépendance `league/flysystem-sftp-v3` devrait déjà être installée. Vérifiez dans `composer.json` :

```bash
composer show league/flysystem-sftp-v3
```

Si non installé :

```bash
composer require league/flysystem-sftp-v3
```

### Étape 2 : Configuration de l'environnement

Ajoutez ces variables dans votre fichier `.env` :

```env
# SFTP Configuration
SFTP_TIMEOUT=30
SFTP_MAX_FILE_SIZE=10240
```

**Notes** :
- `SFTP_TIMEOUT` : Timeout de connexion en secondes (30 secondes par défaut)
- `SFTP_MAX_FILE_SIZE` : Taille maximale des fichiers en KB (10 MB par défaut)

### Étape 3 : Exécuter les migrations

Exécutez les migrations pour créer les tables nécessaires :

```bash
php artisan migrate
```

Cela créera les tables :
- `sftp_configurations` : Configurations SFTP
- `sftp_uploads` : Historique des uploads

### Étape 4 : Mettre à jour les permissions

Si votre base de données existe déjà et contient des rôles, vous devez **régénérer les permissions** :

**Option A : Réinitialiser complètement (développement uniquement)** :

```bash
php artisan migrate:fresh --seed
```

⚠️ **ATTENTION** : Ceci supprimera toutes les données !

**Option B : Ajouter manuellement les permissions (recommandé pour la production)** :

Connectez-vous à votre base de données et exécutez :

```sql
-- Ajouter les nouvelles permissions
INSERT INTO permissions (name, guard_name, created_at, updated_at)
VALUES
  ('manage-sftp-config', 'web', NOW(), NOW()),
  ('upload-sftp-pdf', 'web', NOW(), NOW());

-- Attribuer toutes les permissions au Super-admin (ID du rôle Super-admin)
INSERT INTO role_has_permissions (permission_id, role_id)
SELECT id, (SELECT id FROM roles WHERE name = 'Super-admin' LIMIT 1)
FROM permissions
WHERE name IN ('manage-sftp-config', 'upload-sftp-pdf');

-- Attribuer la permission d'upload aux Admin
INSERT INTO role_has_permissions (permission_id, role_id)
SELECT id, (SELECT id FROM roles WHERE name = 'Admin' LIMIT 1)
FROM permissions
WHERE name = 'upload-sftp-pdf';
```

Puis videz le cache des permissions :

```bash
php artisan cache:forget spatie.permission.cache
```

### Étape 5 : Créer le dossier de stockage temporaire

Créez le répertoire pour le stockage temporaire des fichiers :

```bash
mkdir -p storage/app/sftp_uploads
chmod 755 storage/app/sftp_uploads
```

Sur Windows :

```cmd
mkdir storage\app\sftp_uploads
```

### Étape 6 : Vérifier la configuration des queues

La fonctionnalité utilise des jobs en arrière-plan. Assurez-vous que le worker de queue fonctionne :

```bash
php artisan queue:work database --stop-when-empty
```

Pour un environnement de production, configurez un service superviseur (Supervisor sous Linux) :

```ini
[program:laravel-worker]
process_name=%(program_name)s_%(process_num)02d
command=php /path/to/your/project/artisan queue:work database --sleep=3 --tries=3 --max-time=3600
autostart=true
autorestart=true
stopasgroup=true
killasgroup=true
user=www-data
numprocs=1
redirect_stderr=true
stdout_logfile=/path/to/your/project/storage/logs/worker.log
stopwaitsecs=3600
```

### Étape 7 : Tester l'installation

1. **Connectez-vous en tant que Super-Admin**

2. **Accédez à la configuration SFTP** :
   - Menu : SFTP > Configuration
   - URL : `/admin/sftp/configuration`

3. **Créez une configuration de test** :
   - Nom : `Test SFTP`
   - Hôte : `sftp.example.com`
   - Port : `22`
   - Username : `votre_username`
   - Password : `votre_password` OU clé privée SSH
   - Dossier distant : `/uploads/pdf`
   - Active : Oui

4. **Testez la connexion** :
   - Cliquez sur le bouton "Tester" pour vérifier la connexion

5. **Testez l'upload (en tant qu'Admin)** :
   - Menu : SFTP > Upload PDF
   - URL : `/admin/sftp/upload`
   - Sélectionnez la configuration
   - Uploadez un fichier PDF de test

---

## Sécurité

### Chiffrement des credentials

Les mots de passe et clés privées SSH sont **automatiquement chiffrés** dans la base de données en utilisant `Crypt::encryptString()` de Laravel.

⚠️ **Important** : Ne perdez jamais votre `APP_KEY` dans `.env` car vous ne pourriez plus déchiffrer les credentials !

### Permissions

- **manage-sftp-config** : Réservé aux Super-Admin uniquement
- **upload-sftp-pdf** : Accessible aux Admin et Super-Admin

Les permissions sont contrôlées à plusieurs niveaux :
- Routes (middleware `permission`)
- Sidebar (directives `@can`)
- Composants Livewire

### Validation des fichiers

Les fichiers uploadés sont strictement validés :
- **Format** : PDF uniquement (vérification MIME type + extension)
- **Taille** : Maximum configurable (10 MB par défaut)
- **Sanitization** : Les noms de fichiers sont nettoyés

---

## Utilisation

### Pour les Super-Admin

#### 1. Créer une configuration SFTP

1. Menu : **SFTP > Configuration**
2. Cliquez sur **"Nouvelle configuration"**
3. Remplissez le formulaire :
   - **Nom** : Nom descriptif (ex: "Production SFTP")
   - **Hôte** : Adresse du serveur SFTP
   - **Port** : Port SSH (22 par défaut)
   - **Nom d'utilisateur** : Username SFTP
   - **Mot de passe** : Password SFTP (OU clé privée)
   - **Clé privée SSH** : Alternative au mot de passe
   - **Dossier distant** : Chemin d'upload sur le serveur
   - **Active** : Activerla configuration

4. Cliquez sur **"Créer"**
5. Testez la connexion avec le bouton **"Tester"**

#### 2. Gérer les configurations

- **Éditer** : Modifier une configuration existante
- **Tester** : Vérifier la connexion SFTP
- **Activer/Désactiver** : Rendre une config disponible ou non
- **Supprimer** : Effacer une configuration (et tous ses uploads associés)

### Pour les Admin

#### 1. Uploader un fichier PDF

1. Menu : **SFTP > Upload PDF**
2. Sélectionnez une **configuration SFTP**
3. Cliquez sur **"Choisir un fichier"**
4. Sélectionnez un fichier PDF (max 10 MB)
5. Cliquez sur **"Uploader"**
6. Le fichier est ajouté à la file d'attente

#### 2. Suivre les uploads

La page d'upload affiche :
- **Statistiques** : Total, En attente, En cours, Complétés, Échoués
- **Filtres** : Recherche par nom de fichier, filtre par statut
- **Historique** : Liste de tous les uploads avec détails

#### 3. Gérer les uploads échoués

Pour un upload échoué :
- Cliquez sur **"Réessayer"** pour relancer l'upload
- Survolez **"Erreur"** pour voir le message d'erreur
- Cliquez sur **"Supprimer"** pour effacer l'enregistrement

---

## Troubleshooting

### Problème : "Aucune configuration SFTP active disponible"

**Solution** :
1. Connectez-vous en tant que Super-Admin
2. Créez une configuration SFTP
3. Assurez-vous qu'elle est **Active**
4. Testez la connexion

### Problème : Les uploads restent en statut "En attente"

**Cause** : Le worker de queue ne fonctionne pas

**Solution** :
```bash
# Vérifier si des jobs sont en attente
php artisan queue:work database --once

# Lancer le worker en continu
php artisan queue:work database
```

### Problème : "Échec de la connexion SFTP"

**Causes possibles** :
1. Credentials incorrects
2. Serveur SFTP inaccessible
3. Port bloqué par un firewall
4. Dossier distant n'existe pas

**Solution** :
1. Vérifiez les credentials
2. Testez la connexion manuellement :
   ```bash
   sftp -P 22 username@host
   ```
3. Vérifiez les logs Laravel : `storage/logs/laravel.log`

### Problème : "Le fichier doit être au format PDF"

**Cause** : Le fichier n'est pas un vrai PDF ou est corrompu

**Solution** :
- Assurez-vous que le fichier est un PDF valide
- Vérifiez l'extension (.pdf)
- Essayez avec un autre fichier PDF

### Problème : "Le fichier est trop volumineux"

**Solution** :
1. Réduisez la taille du PDF
2. OU augmentez la limite dans `.env` :
   ```env
   SFTP_MAX_FILE_SIZE=20480  # 20 MB
   ```
3. Redémarrez l'application : `php artisan config:cache`

---

## Maintenance

### Nettoyer les anciens uploads

Les fichiers locaux sont automatiquement supprimés après un upload réussi. Pour nettoyer manuellement :

```bash
# Supprimer les fichiers temporaires de plus de 7 jours
find storage/app/sftp_uploads -type f -mtime +7 -delete
```

### Vérifier les logs

Tous les événements SFTP sont loggés :

```bash
tail -f storage/logs/laravel.log | grep SFTP
```

### Monitorer les uploads échoués

Vérifiez régulièrement la page d'upload pour identifier les problèmes récurrents.

---

## Rollback (en cas de problème)

Si vous devez annuler cette fonctionnalité :

1. **Supprimer les routes** dans `routes/web.php` :
   - Supprimez les routes `admin.sftp.*`

2. **Masquer les liens** dans la sidebar :
   - Commentez la section SFTP dans `resources/views/components/layouts/app/sidebar.blade.php`

3. **Rollback des migrations** :
   ```bash
   php artisan migrate:rollback --step=2
   ```

4. **Supprimer les permissions** (optionnel) :
   ```bash
   php artisan tinker
   >>> Permission::whereIn('name', ['manage-sftp-config', 'upload-sftp-pdf'])->delete();
   ```

---

## Support

Pour toute question ou problème :

1. Consultez les logs : `storage/logs/laravel.log`
2. Vérifiez la configuration SFTP
3. Testez manuellement la connexion SFTP
4. Vérifiez que le worker de queue fonctionne

---

## Changelog

### Version 1.0 (2025-11-17)

- ✅ Configuration SFTP (Super-Admin)
- ✅ Upload de PDF vers SFTP (Admin)
- ✅ Upload asynchrone via jobs
- ✅ Chiffrement des credentials
- ✅ Validation stricte des PDFs
- ✅ Historique et statistiques des uploads
- ✅ Retry automatique en cas d'échec
- ✅ Interface avec Flux UI
- ✅ Permissions granulaires
