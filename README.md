# DISPO-APP

Application web de gestion touristique développée avec Laravel 12 et Livewire.

## 📚 Documentation

### Documentation principale
- **[📊 Analyse complète de l'application](ANALYSE_APPLICATION.md)** - Document technique exhaustif (1600+ lignes)
- **[🎯 Contexte du projet](.claude/project_context.md)** - Vue d'ensemble rapide
- **[📝 Instructions de développement](.claude/instructions.md)** - Conventions et bonnes pratiques

### Documentation spécifique
- [Configuration API Apidae](APIDAE_SETUP.md)
- [Planification automatique](APIDAE_SCHEDULING.md)
- [Configuration Mailjet](MAILJET_SETUP.md)
- [Module Qualification](Outil-qualification.md)
- [Queues, jobs et opérations sur fichiers](QUEUES_JOBS_ET_FICHIERS.md)

## 🚀 Quick Start

### Prérequis
- PHP 8.2+
- Composer
- Node.js 18+
- SQLite/MySQL

### Installation

```bash
# Cloner le repository
git clone [repository-url]
cd dispo-app

# Installer les dépendances PHP
composer install

# Installer les dépendances JavaScript
npm install

# Copier et configurer l'environnement
cp .env.example .env
php artisan key:generate

# Créer la base de données
touch database/database.sqlite

# Lancer les migrations et seeders
php artisan migrate --seed

# Compiler les assets
npm run build

# Lancer le serveur
php artisan serve
```

### Configuration requise

Éditer le fichier `.env` :

```env
# API Apidae
APIDAE_API_KEY=votre_cle_api
APIDAE_PROJECT_ID=votre_projet_id
APIDAE_SELECTION_ID=votre_selection_id

# Mailjet
MAILJET_APIKEY=votre_cle_mailjet
MAILJET_APISECRET=votre_secret_mailjet
```

## 🏗️ Architecture

### Stack technique
- **Backend** : Laravel 12, PHP 8.2+
- **Frontend** : Livewire 3.x, Tailwind CSS 4, DaisyUI 5
- **Base de données** : SQLite (dev) / MySQL (prod)
- **Queue** : Database driver
- **Cache** : Database
- **APIs** : Apidae, Mailjet

### Modules principaux
1. **Module Disponibilités** - Gestion des hébergements touristiques
2. **Module Qualification** - Collecte de données visiteurs

## 👥 Système de rôles

| Rôle | Accès |
|------|-------|
| Super-admin | Accès total + gestion utilisateurs |
| Admin | Accès total sauf gestion utilisateurs |
| Qualification | Module qualification uniquement |
| Disponibilites | Module hébergements uniquement |
| Utilisateurs | Formulaires uniquement |

## 🔧 Commandes utiles

```bash
# Récupérer les hébergements depuis Apidae
php artisan apidae:fetch --all

# Lancer la queue de jobs
php artisan queue:work

# Lancer les tests
php artisan test

# Clear cache
php artisan cache:clear
php artisan view:clear
php artisan config:clear
```

## 📈 État du projet

- ✅ **Fonctionnel** : Application en production
- ⚠️ **Tests** : 0% coverage - À implémenter
- ⚠️ **Scheduler** : Non configuré - Voir [APIDAE_SCHEDULING.md](APIDAE_SCHEDULING.md)
- ⚠️ **Sécurité** : Tokens sans expiration - À corriger

## 📝 Licence

Propriétaire

---

**Pour toute nouvelle fonctionnalité, consultez d'abord [ANALYSE_APPLICATION.md](ANALYSE_APPLICATION.md)**
