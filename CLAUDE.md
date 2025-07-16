# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## Project Overview

This is a Laravel 12 application with Livewire for building a tourism accommodation management system. The application integrates with the Apidae API to fetch and manage accommodation data from French tourism databases.

## Key Technologies

- **Backend**: Laravel 12 with PHP 8.2+
- **Frontend**: Livewire + Flux UI components
- **Styling**: Tailwind CSS 4.0 with DaisyUI
- **Database**: SQLite (development), configurable for production
- **Testing**: Pest PHP testing framework
- **Build Tool**: Vite
- **External API**: Apidae Tourism API integration

## Common Commands

### Development
```bash
# Start development server with queue worker and asset compilation
composer run dev

# Alternative: Start individual services
php artisan serve                    # Start Laravel server
php artisan queue:listen --tries=1  # Start queue worker
npm run dev                         # Start Vite for asset compilation
```

### Database
```bash
php artisan migrate                 # Run migrations
php artisan db:seed                 # Run seeders
php artisan migrate:fresh --seed    # Fresh migration with seeding
```

### Testing
```bash
composer run test                   # Run full test suite
php artisan test                    # Alternative test command
vendor/bin/pest                     # Run Pest tests directly
vendor/bin/pest --filter=TestName   # Run specific test
```

### Code Quality
```bash
vendor/bin/pint                     # Format code using Laravel Pint
```

### Asset Management
```bash
npm run build                       # Build for production
npm run dev                         # Development build with watching
```

### Apidae API Integration
```bash
php artisan apidae:fetch            # Fetch accommodations from API (150 default)
php artisan apidae:fetch --test     # Use test data instead of API
php artisan apidae:fetch --limit=50 # Limit number of accommodations
php artisan apidae:fetch --simple   # Simple query without criteria
```

### 🕐 Synchronisation Automatique
La synchronisation Apidae est maintenant automatisée via le scheduler Laravel :

```bash
# Vérifier les tâches planifiées
php artisan schedule:list

# Tester la planification manuellement
php artisan schedule:run

# Dispatcher un job de synchronisation manuellement
php artisan tinker
>>> App\Jobs\SyncApidaeData::dispatch(100);

# Surveiller les workers de queue
php artisan queue:work --queue=apidae-sync

# Vérifier les logs de synchronisation
grep "Apidae" storage/logs/laravel.log | tail -20
```

## Application Architecture

### Core Models
- **Accommodation**: Main model representing tourism accommodations with Apidae integration
  - Fields: apidae_id, name, city, email, phone, website, description, type, status
  - Scopes: active(), pending(), withContact(), search()
  - Location: `app/Models/Accommodation.php`

### MVC Architecture (Refactorisé - Juillet 2025)
- **Controllers**: `AccommodationController` avec injection de dépendances
  - Méthodes publiques pour gestion de statut (`manage`, `updateStatus`)
- **Services**: `AccommodationService` et `ApidaeService` pour la logique métier
- **Requests**: `AccommodationFilterRequest` pour validation centralisée
- **Jobs**: `SyncApidaeData` pour synchronisation automatique en queue
- **Modèle**: `Accommodation` avec méthode `getManageUrl()` pour liens uniques

### Interface Web
- **Page des hébergements**: Vue MVC traditionnelle (`accommodations/index.blade.php`)
  - Filtres: recherche, statut, ville, type, informations de contact
  - Pagination: 100 éléments par page
  - Statistiques et classements des villes
  - Icônes de gestion de statut avec liens uniques
- **Page de gestion de statut publique**: Interface pour les hébergeurs
  - Accessible sans authentification via lien unique
  - Boutons "Activer" et "Désactiver"
  - Informations complètes de l'hébergement
  - Interface responsive et moderne

### Key Features
- **Apidae API Integration**: Fetches accommodation data from French tourism API
- **Synchronisation Automatique**: Quotidienne à 5h00 via scheduler Laravel
- **Advanced Filtering**: Multiple filter options for accommodations
- **User Authentication**: Laravel Breeze-style authentication for admin interface
- **Dashboard**: Statistics and management interface
- **Settings**: User profile, password, appearance management
- **Performance**: Cache intelligent et index de base de données
- **Gestion de Statut Publique**: Liens uniques pour les hébergeurs
  - Pages publiques sans authentification
  - Basées sur l'identifiant unique apidae_id
  - Interface simple avec boutons Activer/Désactiver
  - Accessible via liens cliquables sur les cartes d'hébergement

### API Integration
The application integrates with the Apidae API for French tourism data:
- **Command**: `FetchApidaeData` in `app/Console/Commands/`
- **Service**: `ApidaeService` in `app/Services/`
- **Job**: `SyncApidaeData` in `app/Jobs/` pour synchronisation automatique
- **Configuration**: Requires APIDAE_API_KEY, APIDAE_PROJECT_ID, APIDAE_SELECTION_ID in .env
- **Data Processing**: Handles accommodation data parsing and contact information extraction
- **Scheduler**: Synchronisation automatique quotidienne à 5h00 et hebdomadaire le dimanche

### Database Schema
- **Users**: Standard Laravel authentication
- **Accommodations**: Tourism accommodations with Apidae integration
- **Cache/Queue**: Standard Laravel infrastructure tables

### Routes Structure
- **Authentication**: Standard Laravel auth routes
- **Dashboard**: Main application interface
- **Accommodations**: List and management interface (authentifiée)
- **Settings**: User preferences and profile management
- **Gestion Publique**: Routes publiques pour les hébergeurs
  - `GET /accommodation/{apidae_id}/manage` - Page de gestion
  - `POST /accommodation/{apidae_id}/status` - Mise à jour du statut
  - Pas d'authentification requise

## Environment Configuration

Copy `.env.example` to `.env` and configure:
- Database connection (SQLite by default)
- Apidae API credentials for tourism data integration
- Mail settings for user notifications
- Application settings (name, URL, etc.)

## UI Framework

The application uses Flux UI components with Tailwind CSS styling:
- Components located in `resources/views/components/`
- Flux components in `resources/views/flux/`
- MVC views in `resources/views/accommodations/`
- Page publique in `resources/views/accommodation/manage.blade.php`

## Testing Strategy

Tests are organized using Pest PHP:
- **Feature Tests**: Authentication, dashboard, settings functionality
- **Apidae Sync Tests**: Tests complets pour synchronisation automatique
- **Scheduled Tasks Tests**: Tests de planification Laravel
- **Unit Tests**: Model logic and business rules
- **Database**: In-memory SQLite for testing
- **Configuration**: `phpunit.xml` with proper test environment setup

### Tests de Synchronisation Automatique
```bash
# Tests de synchronisation Apidae
vendor/bin/pest tests/Feature/ApidaeSyncTest.php

# Tests de planification
vendor/bin/pest tests/Feature/ScheduledTasksTest.php

# Tous les tests
vendor/bin/pest
```

### Couverture de Tests
- ✅ **Job SyncApidaeData** : Création, exécution, gestion d'erreurs
- ✅ **Service ApidaeService** : Traitement et sanitisation des données
- ✅ **Cache management** : Nettoyage automatique après sync
- ✅ **Queue system** : Configuration et dispatch des jobs
- ✅ **Data validation** : Validation email, téléphone, URL
- ✅ **Update vs Create** : Logique d'upsert des accommodations