# ANALYSE DÉTAILLÉE - DISPO-APP

> Document d'analyse technique exhaustive de l'application web dispo-app
> Date d'analyse : 17 novembre 2025
> Version Laravel : 12.0

## Table des matières

1. [Architecture Globale](#1-architecture-globale)
2. [Structure des Dossiers](#2-structure-des-dossiers)
3. [Backend - Architecture Détaillée](#3-backend---architecture-détaillée)
4. [Frontend - Composants Livewire](#4-frontend---composants-livewire)
5. [Base de Données](#5-base-de-données)
6. [Configuration](#6-configuration)
7. [Routes et Navigation](#7-routes-et-navigation)
8. [Fonctionnalités Métier](#8-fonctionnalités-métier)
9. [Sécurité](#9-sécurité)
10. [Tests](#10-tests)
11. [API Externes](#11-api-externes)
12. [Assets et Ressources](#12-assets-et-ressources)
13. [Documentation](#13-documentation)
14. [Problèmes et Améliorations](#14-problèmes-et-améliorations)
15. [Points Forts](#15-points-forts)
16. [Conclusion](#16-conclusion)

---

## 1. ARCHITECTURE GLOBALE

### Stack Technique

| Catégorie | Technologie | Version | Rôle |
|-----------|-------------|---------|------|
| **Framework Backend** | Laravel | 12.0 | Framework PHP principal |
| **Framework Frontend** | Livewire | 3.x | Composants réactifs |
| **UI Framework** | Flux | 2.1.1 | Composants UI |
| **Template Engine** | Volt | 1.7.0 | Templates Livewire |
| **Langage** | PHP | 8.2+ | Langage serveur |
| **Base de données** | SQLite/MySQL | - | Stockage données |
| **CSS Framework** | Tailwind CSS | 4.0.7 | Styles |
| **UI Library** | DaisyUI | 5.0.43 | Composants UI |
| **Build Tool** | Vite | 6.0 | Bundler assets |
| **Queue System** | Laravel Queue | Database | Jobs asynchrones |
| **Cache** | Database | - | Système de cache |

### Packages Principaux

```
spatie/laravel-permission (6.23) - Gestion avancée des rôles et permissions
mailjet/mailjet-apiv3-php (1.6) - Service d'envoi d'emails transactionnels
maatwebsite/excel (3.1) - Export/Import Excel
pestphp/pest (3.8) - Framework de tests moderne
```

### Architecture MVC

L'application suit le pattern MVC classique de Laravel avec une couche supplémentaire de composants Livewire pour la réactivité :

```
┌─────────────┐     ┌──────────────┐     ┌─────────────┐
│   Routes    │────▶│  Controllers │────▶│   Models    │
└─────────────┘     └──────────────┘     └─────────────┘
       │                    │                     │
       ▼                    ▼                     ▼
┌─────────────┐     ┌──────────────┐     ┌─────────────┐
│  Livewire   │────▶│   Services   │────▶│   Database  │
│ Components  │     └──────────────┘     └─────────────┘
└─────────────┘
```

### Points d'Entrée

- **Web** : `c:\Users\casan\Desktop\VT\PANEL\dispo-app\public\index.php`
- **CLI** : `c:\Users\casan\Desktop\VT\PANEL\dispo-app\artisan`
- **Queue Worker** : `php artisan queue:work`

---

## 2. STRUCTURE DES DOSSIERS

### Arborescence Principale

```
dispo-app/
├── app/                    # Code métier principal
│   ├── Console/           # Commandes Artisan (3 commandes)
│   ├── Exports/           # Classes d'export (1 export Excel)
│   ├── Http/
│   │   ├── Controllers/   # Contrôleurs HTTP (3)
│   │   └── Middleware/    # Middlewares (3)
│   ├── Jobs/              # Jobs asynchrones (2)
│   ├── Livewire/          # Composants Livewire (20+)
│   ├── Models/            # Modèles Eloquent (3)
│   ├── Providers/         # Service Providers (1)
│   └── Services/          # Services métier (4)
├── bootstrap/             # Fichiers de démarrage
├── config/                # Configuration (13 fichiers)
├── database/
│   ├── migrations/        # Migrations (10)
│   ├── seeders/          # Seeders (2)
│   └── database.sqlite   # Base SQLite (2.9 MB)
├── public/                # Assets publics
│   ├── build/            # Assets compilés
│   └── images/           # Images statiques
├── resources/
│   ├── css/              # Styles source
│   ├── js/               # JavaScript source
│   └── views/            # Templates Blade
│       ├── components/   # Composants réutilisables
│       ├── emails/       # Templates emails
│       ├── livewire/     # Vues Livewire
│       └── qualification/ # Vues qualification
├── routes/                # Définition des routes
├── storage/               # Stockage fichiers/cache
└── tests/                 # Tests (structure présente)
```

### Statistiques du Code

- **Fichiers PHP** : ~60 fichiers
- **Composants Livewire** : 20+ composants
- **Vues Blade** : ~40 templates
- **Migrations** : 10 fichiers
- **Taille totale** : ~15 MB (hors vendor)

---

## 3. BACKEND - ARCHITECTURE DÉTAILLÉE

### Modèles Eloquent

#### User (app/Models/User.php)

```php
Champs:
- id (primary key)
- name (string)
- email (unique)
- email_verified_at (nullable)
- password (hashed)
- approved (boolean, default: false)
- approved_at (nullable datetime)
- remember_token
- timestamps

Méthodes principales:
- isApproved(): bool
- approve(): void (déclenche email)
- disapprove(): void
- initials(): string

Traits:
- HasRoles (Spatie)
- HasFactory
- Notifiable
```

#### Accommodation (app/Models/Accommodation.php)

```php
Champs:
- id
- apidae_id (unique, index)
- name
- city
- email (nullable)
- phone (nullable)
- website (nullable)
- description (text, nullable)
- type (nullable)
- status (enum: 'en_attente', 'disponible', 'indisponible')
- email_sent_at (nullable)
- email_response_token (unique, nullable)
- last_response_at (nullable)
- timestamps

Méthodes:
- generateResponseToken(): string
- markEmailSent(): void
- updateAvailability(bool $available): void

Scopes:
- scopeActive($query)
- scopePending($query)
```

#### Qualification (app/Models/Qualification.php)

```php
Champs:
- id
- city (enum: 'annot', 'colmars-les-alpes', 'entrevaux',
        'la-palud-sur-verdon', 'saint-andre-les-alpes')
- user_id (foreign key)
- current_step (integer, default: 1)
- form_data (JSON)
- completed (boolean, default: false)
- completed_at (nullable)
- timestamps

Relations:
- belongsTo(User::class)

Scopes:
- scopeCompleted($query)
- scopeIncomplete($query)
- scopeForCity($query, $city)
```

### Services Métier

#### MailjetService (app/Services/MailjetService.php)

**Responsabilités** :
- Envoi d'emails via API Mailjet v3.1
- Génération HTML emails
- Gestion templates

**Méthodes** :
```php
sendAvailabilityRequest(Accommodation $accommodation, array $urls): bool
sendUserApprovalEmail(User $user): bool
generateEmailHtml(string $template, array $data): string
```

#### QualificationStatisticsService (app/Services/QualificationStatisticsService.php)

**Responsabilités** :
- Calcul statistiques avancées
- Support multi-bases (SQLite/MySQL)
- Agrégations temporelles

**Méthodes principales** :
```php
getKPIs(array $filters): array
getStatsByCity(array $filters): array
getTemporalEvolution(array $filters): array
getGeographicStats(array $filters): array
getProfileStats(array $filters): array
getDemandStats(array $filters): array
getContactStats(array $filters): array
```

#### RoleService (app/Services/RoleService.php)

**Hiérarchie des rôles** :

| Rôle | Niveau | Permissions |
|------|--------|------------|
| Super-admin | 5 | Toutes + manage-users |
| Admin | 4 | Toutes sauf manage-users |
| Qualification | 3 | view-qualification, edit-qualification |
| Disponibilites | 3 | view-disponibilites, edit-disponibilites |
| Utilisateurs | 1 | fill-forms, view-qualification |

**Méthodes** :
```php
userHasAnyRole(User $user, array $roles): bool
canAccessSystem(User $user): bool
getUserHighestRole(User $user): ?string
syncUserRoles(User $user, array $roles): void
```

#### FrenchGeographyService (app/Services/FrenchGeographyService.php)

**Données gérées** :
- 101 départements français
- 18 régions
- 200+ pays

**Fonctionnalités** :
- Recherche avec normalisation accents
- Validation départements/pays
- Groupement par région

### Console Commands

#### FetchApidaeData (php artisan apidae:fetch)

```bash
Options:
--test       # Utilise 5 hébergements fictifs
--all        # Récupère TOUS les hébergements
--limit=N    # Limite à N hébergements (défaut: 150)
--simple     # Requête sans critères

Fonctionnement:
1. Connexion API Apidae
2. Pagination automatique (20/page)
3. Extraction données (nom, ville, contacts)
4. UpdateOrCreate dans accommodations
5. Logs détaillés
```

#### SendAvailabilityEmails

```bash
php artisan accommodations:send-emails

Processus:
1. Sélection hébergements avec email
2. Création jobs en queue
3. Envoi asynchrone
```

#### MigrateDepartmentsToArray

```bash
php artisan migrate:departments

Migration one-time:
- Convertit department (string) en departments (array)
```

### Jobs Asynchrones

#### SendAccommodationAvailabilityEmail

```php
Queue: database
Timeout: 60 secondes

Process:
1. Génère token unique (bin2hex)
2. Crée URLs de callback
3. Envoie email via MailjetService
4. Marque email_sent_at
5. Log résultat
```

#### SendUserApprovalEmail

```php
Queue: database

Process:
1. Vérifie utilisateur approuvé
2. Envoie email de bienvenue
3. Log résultat
```

### Middlewares

#### EnsureUserIsApproved

```php
Route: toutes sauf auth/*
Comportement:
- Vérifie $user->approved
- Si false → logout + redirect login
- Message: "Votre compte est en attente d'approbation"
```

#### CheckPermission

```php
Usage: ->middleware(['permission:view-qualification,edit-qualification'])
Comportement: Vérifie au moins une permission
```

#### CheckRole

```php
Usage: ->middleware(['role:Admin,Super-admin'])
Comportement: Vérifie au moins un rôle
```

---

## 4. FRONTEND - COMPOSANTS LIVEWIRE

### Composants Principaux

#### AccommodationsList

**Fichier** : `app/Livewire/AccommodationsList.php`
**Vue** : `resources/views/livewire/accommodations-list.blade.php`

**Fonctionnalités** :
- Liste paginée (100/page)
- Filtres multiples (recherche, statut, ville, type, email, phone, website)
- Statistiques temps réel
- Envoi emails massif
- Persistence URL (queryString)

**Propriétés réactives** :
```php
public $search = '';
public $statusFilter = '';
public $cityFilter = '';
public $typeFilter = '';
public $hasEmailFilter = '';
public $hasPhoneFilter = '';
public $hasWebsiteFilter = '';
public $perPage = 100;
```

#### QualificationForm

**Fichier** : `app/Livewire/QualificationForm.php` (654 lignes)
**Vue** : `resources/views/livewire/qualification-form.blade.php`

**Structure multi-étapes** :

```
Étape 1 - Origine
├── Pays (France/Autre)
├── Département(s) - multi-select
├── Email (optionnel)
└── Consentements (newsletter, RGPD)

Étape 2 - Profil
├── Type visiteur (Famille/Couple/Solo/Groupe/Business)
└── Tranches d'âge (multi-select)

Étape 3 - Demandes
├── Date modification
├── Méthode contact (Direct/Mail/Téléphone)
├── Demandes ville (3-6 options/ville)
├── Demandes croisées (autres villes)
├── Demandes générales (20+ catégories)
└── Texte libre
```

**Validation temps réel** :
- Email format
- Au moins un département ou "inconnu"
- Date valide
- Au moins une demande

#### QualificationStatisticsV2

**Fichier** : `app/Livewire/QualificationStatisticsV2.php`
**Vue** : `resources/views/livewire/qualification/statistics-v2.blade.php`

**Graphiques Chart.js** :
- KPIs (cards)
- Évolution temporelle (line chart)
- Répartition géographique (bar chart)
- Profils visiteurs (doughnut)
- Top demandes (horizontal bar)

**Filtres** :
```php
public $selectedCities = [];
public $selectedPeriod = '30d';
public $selectedStatus = 'all';
public $startDate = null;
public $endDate = null;
```

#### DepartmentSelector

**Composant autocomplete** :
- Recherche normalisée (accents)
- Multi-select
- Badges visuels
- Option "Département inconnu"
- Émission événements

#### Composants Admin

##### UsersList

**Gestion utilisateurs** (Super-admin only) :
- Liste avec statut approbation
- Actions: Approuver/Désapprouver
- Gestion rôles (multi-select)
- Filtres et recherche

##### UserRoles

**Assignation rôles** :
- Interface drag & drop
- Validation hiérarchique
- Sauvegarde temps réel

### Composants Authentification

| Composant | Route | Fonction |
|-----------|-------|----------|
| Auth/Login | /login | Connexion utilisateur |
| Auth/Register | /register | Inscription |
| Auth/ForgotPassword | /forgot-password | Récupération MDP |
| Auth/ResetPassword | /reset-password/{token} | Nouveau MDP |
| Auth/VerifyEmail | /verify-email | Vérification email |
| Auth/ConfirmPassword | /confirm-password | Confirmation MDP |

### Composants Settings

| Composant | Route | Fonction |
|-----------|-------|----------|
| Settings/Profile | /settings/profile | Édition profil |
| Settings/Password | /settings/password | Changement MDP |
| Settings/Appearance | /settings/appearance | Préférences UI |
| Settings/DeleteUserForm | - | Suppression compte |

---

## 5. BASE DE DONNÉES

### Schema Principal

#### Table: users

```sql
CREATE TABLE users (
    id BIGINT PRIMARY KEY,
    name VARCHAR(255) NOT NULL,
    email VARCHAR(255) UNIQUE NOT NULL,
    email_verified_at TIMESTAMP NULL,
    password VARCHAR(255) NOT NULL,
    approved BOOLEAN DEFAULT 0,
    approved_at TIMESTAMP NULL,
    remember_token VARCHAR(100) NULL,
    created_at TIMESTAMP,
    updated_at TIMESTAMP
);
```

#### Table: accommodations

```sql
CREATE TABLE accommodations (
    id BIGINT PRIMARY KEY,
    apidae_id VARCHAR(255) UNIQUE NOT NULL,
    name VARCHAR(255) NOT NULL,
    city VARCHAR(255),
    email VARCHAR(255) NULL,
    phone VARCHAR(255) NULL,
    website VARCHAR(255) NULL,
    description TEXT NULL,
    type VARCHAR(255) NULL,
    status ENUM('en_attente', 'disponible', 'indisponible') DEFAULT 'en_attente',
    email_sent_at TIMESTAMP NULL,
    email_response_token VARCHAR(255) UNIQUE NULL,
    last_response_at TIMESTAMP NULL,
    created_at TIMESTAMP,
    updated_at TIMESTAMP,
    INDEX idx_status_city (status, city)
);
```

#### Table: qualifications

```sql
CREATE TABLE qualifications (
    id BIGINT PRIMARY KEY,
    city ENUM('annot', 'colmars-les-alpes', 'entrevaux',
              'la-palud-sur-verdon', 'saint-andre-les-alpes') NOT NULL,
    user_id BIGINT NOT NULL,
    current_step INTEGER DEFAULT 1,
    form_data JSON,
    completed BOOLEAN DEFAULT 0,
    completed_at TIMESTAMP NULL,
    created_at TIMESTAMP,
    updated_at TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_city_completed (city, completed)
);
```

### Tables Spatie Permission

```sql
permissions (id, name, guard_name, created_at, updated_at)
roles (id, name, guard_name, created_at, updated_at)
model_has_permissions (permission_id, model_type, model_id)
model_has_roles (role_id, model_type, model_id)
role_has_permissions (permission_id, role_id)
```

### Migrations

| Ordre | Fichier | Description |
|-------|---------|-------------|
| 1 | create_users_table | Tables users, password_reset_tokens, sessions |
| 2 | create_cache_table | Tables cache, cache_locks |
| 3 | create_jobs_table | Tables jobs, job_batches, failed_jobs |
| 4 | create_accommodations_table | Table accommodations initiale |
| 5 | add_fields_to_accommodations | Ajout phone, website, description, type |
| 6 | add_email_tracking_fields | Ajout tracking emails |
| 7 | update_accommodation_status | Migration valeurs status |
| 8 | add_approved_field_to_users | Ajout système approbation |
| 9 | create_qualifications_table | Table qualifications |
| 10 | create_permission_tables | Tables Spatie permissions |

---

## 6. CONFIGURATION

### Variables d'Environnement (.env)

```env
# Application
APP_NAME="Dispo App"
APP_ENV=local
APP_KEY=base64:xxxxx
APP_DEBUG=true
APP_URL=http://localhost

# Database
DB_CONNECTION=sqlite
DB_DATABASE=database/database.sqlite

# Queue & Cache
QUEUE_CONNECTION=database
CACHE_STORE=database
SESSION_DRIVER=database

# API Apidae
APIDAE_API_KEY=xxxxx
APIDAE_PROJECT_ID=xxxxx
APIDAE_SELECTION_ID=xxxxx

# Mailjet
MAILJET_APIKEY=xxxxx
MAILJET_APISECRET=xxxxx

# Mail
MAIL_MAILER=mailjet
MAIL_FROM_ADDRESS=noreply@example.com
MAIL_FROM_NAME="${APP_NAME}"
```

### Fichiers Configuration Principaux

#### config/services.php

```php
'mailjet' => [
    'key' => env('MAILJET_APIKEY'),
    'secret' => env('MAILJET_APISECRET'),
    'from' => [
        'address' => env('MAIL_FROM_ADDRESS'),
        'name' => env('MAIL_FROM_NAME'),
    ],
],
```

#### config/permission.php

```php
'cache' => [
    'expiration_time' => 24 * 60,
    'key' => 'spatie.permission.cache',
    'store' => 'default',
],
'teams' => false,
```

#### config/french_geography.php

Structure des données :
```php
'departments' => [
    '01' => ['name' => 'Ain', 'region' => 'Auvergne-Rhône-Alpes'],
    // ... 101 départements
],
'regions' => [
    'Auvergne-Rhône-Alpes' => ['01', '03', '07', ...],
    // ... 18 régions
],
'countries' => [
    'France', 'Allemagne', 'Belgique', ...
    // ... 200+ pays
],
```

### Composer Dependencies

```json
{
    "require": {
        "php": "^8.2",
        "laravel/framework": "^12.0",
        "livewire/livewire": "^3.0",
        "livewire/volt": "^1.7",
        "livewire/flux": "^2.1",
        "spatie/laravel-permission": "^6.23",
        "maatwebsite/excel": "^3.1",
        "mailjet/mailjet-apiv3-php": "^1.6"
    },
    "require-dev": {
        "pestphp/pest": "^3.8",
        "laravel/pint": "^1.18"
    }
}
```

### Package.json

```json
{
    "dependencies": {
        "@tailwindcss/vite": "^4.0.7",
        "tailwindcss": "^4.0.7",
        "vite": "^6.0",
        "axios": "^1.7.4"
    },
    "devDependencies": {
        "daisyui": "^5.0.43"
    }
}
```

---

## 7. ROUTES ET NAVIGATION

### Routes Web (routes/web.php)

#### Routes Publiques

```php
Route::get('/', fn() => view('welcome'));
Route::get('/accommodation/response', AccommodationResponseController::class);
```

#### Routes Authentifiées

```php
Route::middleware(['auth', 'verified', 'approved'])->group(function () {

    // Dashboard
    Route::get('/dashboard', fn() => view('dashboard'))->name('dashboard');

    // Settings
    Route::prefix('settings')->group(function () {
        Route::get('/profile', fn() => view('settings.profile'));
        Route::get('/password', fn() => view('settings.password'));
        Route::get('/appearance', fn() => view('settings.appearance'));
    });

    // Accommodations (permission: view-disponibilites)
    Route::middleware(['permission:view-disponibilites'])->group(function () {
        Route::get('/accommodations', fn() => view('accommodations'));
    });

    // Admin (permission: manage-users)
    Route::middleware(['permission:manage-users'])->prefix('admin')->group(function () {
        Route::get('/users', fn() => view('admin.users'));
    });

    // Qualification Module
    Route::prefix('qualification')->group(function () {

        // View level
        Route::middleware(['permission:view-qualification'])->group(function () {
            Route::get('/', [QualificationController::class, 'index']);
            Route::get('/statistiques', fn() => view('qualification.statistics'));
            Route::get('/export', [QualificationController::class, 'export']);
            Route::get('/{city}', [QualificationController::class, 'dashboard'])
                ->where('city', 'annot|colmars-les-alpes|entrevaux|la-palud-sur-verdon|saint-andre-les-alpes');
        });

        // Form level
        Route::middleware(['permission:fill-forms,edit-qualification'])->group(function () {
            Route::get('/{city}/formulaire01', [QualificationController::class, 'form']);
            Route::post('/save', [QualificationController::class, 'save']);
        });

        // Edit level
        Route::middleware(['permission:edit-qualification'])->group(function () {
            Route::get('/{city}/data', [QualificationController::class, 'data']);
            Route::get('/{city}/data/{id}/edit', [QualificationController::class, 'edit']);
        });
    });
});
```

### Routes Auth (routes/auth.php)

```php
// Guest routes
Route::middleware('guest')->group(function () {
    Route::get('login', Login::class)->name('login');
    Route::get('register', Register::class)->name('register');
    Route::get('forgot-password', ForgotPassword::class)->name('password.request');
    Route::get('reset-password/{token}', ResetPassword::class)->name('password.reset');
});

// Authenticated routes
Route::middleware('auth')->group(function () {
    Route::get('verify-email', VerifyEmail::class)->name('verification.notice');
    Route::get('verify-email/{id}/{hash}', VerifyEmailController::class)
        ->middleware(['signed', 'throttle:6,1'])->name('verification.verify');
    Route::get('confirm-password', ConfirmPassword::class)->name('password.confirm');
    Route::post('logout', [AuthController::class, 'logout'])->name('logout');
});
```

### Navigation Dashboard

Le dashboard principal affiche des tuiles selon les permissions :

```php
if (can('view-disponibilites')) → Tuile "Disponibilités Hébergements"
if (can('view-qualification')) → Tuile "Qualification Touristique"
if (can('manage-users')) → Tuile "Gestion Utilisateurs"
```

---

## 8. FONCTIONNALITÉS MÉTIER

### Module 1 : Disponibilités Hébergements

#### Workflow Complet

```
1. RÉCUPÉRATION DONNÉES
   └─ Commande: php artisan apidae:fetch --all
      ├─ Connexion API Apidae
      ├─ Pagination (20/page)
      ├─ Extraction données
      └─ Stockage BDD (updateOrCreate)

2. GESTION INTERFACE
   └─ Route: /accommodations
      ├─ Liste filtrée/paginée
      ├─ Statistiques temps réel
      └─ Actions disponibles

3. SOLLICITATION EMAIL
   └─ Bouton: "Envoyer les emails"
      ├─ Sélection hébergements avec email
      ├─ Création jobs queue
      └─ Envoi asynchrone (Mailjet)

4. TRAITEMENT RÉPONSES
   └─ Route: /accommodation/response?token=xxx&response=xxx
      ├─ Vérification token
      ├─ Mise à jour statut
      └─ Page confirmation
```

#### Statuts Hébergement

- **en_attente** : Initial, pas encore contacté
- **disponible** : A répondu positivement
- **indisponible** : A répondu négativement

### Module 2 : Qualification Touristique

#### Villes Gérées

1. **Annot** - Village perché des Alpes-de-Haute-Provence
2. **Colmars-les-Alpes** - Cité fortifiée
3. **Entrevaux** - Village médiéval
4. **La Palud-sur-Verdon** - Porte des Gorges du Verdon
5. **Saint-André-les-Alpes** - Station touristique

#### Workflow Qualification

```
1. SÉLECTION VILLE
   └─ Route: /qualification
      └─ Choix parmi 5 villes

2. REMPLISSAGE FORMULAIRE
   └─ Route: /qualification/{city}/formulaire01
      ├─ Étape 1: Origine (pays, départements, email)
      ├─ Étape 2: Profil (type visiteur, âges)
      └─ Étape 3: Demandes (spécifiques, générales)

3. SAUVEGARDE
   ├─ Brouillon automatique (current_step)
   └─ Validation finale (completed = true)

4. EXPLOITATION
   └─ Routes: /qualification/statistiques, /qualification/export
      ├─ Statistiques Chart.js
      ├─ Filtres avancés
      └─ Export Excel
```

#### Demandes Spécifiques par Ville

**Annot** :
- Randonnées et sentiers
- Patrimoine historique
- Artisanat local

**Colmars-les-Alpes** :
- Fortifications Vauban
- Activités montagne
- Produits du terroir

**Entrevaux** :
- Citadelle
- Train des Pignes
- Animations médiévales

**La Palud-sur-Verdon** :
- Gorges du Verdon
- Sports nautiques
- Escalade

**Saint-André-les-Alpes** :
- Lac de Castillon
- Vol libre
- VTT

---

## 9. SÉCURITÉ

### Authentification et Autorisation

#### Système d'Authentification

**Stack** : Laravel Breeze (Livewire)

**Fonctionnalités** :
- Inscription avec validation email
- Connexion sécurisée (bcrypt 12 rounds)
- Remember me
- Réinitialisation mot de passe
- CSRF protection automatique

#### Système d'Approbation

**Workflow** :
1. Inscription → `approved = false`
2. Super-admin approuve → `approved = true`
3. Email automatique envoyé
4. Accès autorisé à l'application

**Middleware** : `EnsureUserIsApproved`

#### Système de Permissions

**Architecture Spatie** :

```
Permissions (6)              Rôles (5)
├─ manage-users       ──────► Super-admin (niveau 5)
├─ view-qualification ──────► Admin (niveau 4)
├─ edit-qualification ──────► Qualification (niveau 3)
├─ view-disponibilites ─────► Disponibilites (niveau 3)
├─ edit-disponibilites ─────► Utilisateurs (niveau 1)
└─ fill-forms
```

**Utilisation** :
```php
// Blade
@can('view-qualification')

// Routes
->middleware(['permission:view-qualification'])

// Controllers
if ($user->can('edit-qualification'))
```

### Validation et Protection

#### Validation Données

**Livewire** :
```php
protected $rules = [
    'email' => 'required|email',
    'departments' => 'required|array|min:1',
    'visitor_profile' => 'required|in:famille,couple,solo,groupe',
    'contact_date' => 'required|date|before_or_equal:today',
];
```

#### Protection XSS

- Échappement automatique Blade : `{{ $variable }}`
- Raw output contrôlé : `{!! $html !!}`
- Purification HTML emails

#### Protection CSRF

- Tokens automatiques formulaires
- Vérification middleware
- Régénération session

#### Protection SQL Injection

- Eloquent ORM (requêtes préparées)
- Query Builder sécurisé
- Pas de raw queries non contrôlées

### Sécurité API

#### Tokens Uniques

**Accommodation Response** :
```php
$token = bin2hex(random_bytes(32)); // 64 caractères
```

**Problème identifié** : Pas d'expiration des tokens

### Points d'Amélioration Sécurité

1. **Tokens avec TTL** : Ajouter expiration 24-48h
2. **Rate Limiting** : Limiter tentatives sur callbacks
3. **2FA** : Authentification deux facteurs pour admins
4. **Audit Log** : Traçabilité actions sensibles
5. **Encryption** : Chiffrement données sensibles

---

## 10. TESTS

### Configuration Tests

**Framework** : Pest PHP 3.8

**Structure** :
```
tests/
├── Feature/       # Tests fonctionnels
├── Unit/          # Tests unitaires
├── Pest.php       # Configuration Pest
└── TestCase.php   # Classe de base
```

**Commandes** :
```bash
php artisan test           # Lance tous les tests
vendor/bin/pest            # Lance Pest directement
php artisan test --parallel # Tests en parallèle
```

### État Actuel

- ✅ Structure configurée
- ❌ 0 tests écrits
- ❌ 0% coverage

### Tests Recommandés

#### Tests Feature Critiques

```php
// Feature/AccommodationTest.php
test('can fetch accommodations from apidae api')
test('can send availability emails')
test('can process accommodation response')
test('updates accommodation status correctly')

// Feature/QualificationTest.php
test('can complete multi-step form')
test('saves draft automatically')
test('validates form data correctly')
test('exports data to excel')

// Feature/AuthTest.php
test('unapproved users cannot access app')
test('approved users can login')
test('roles and permissions work correctly')
```

#### Tests Unit Services

```php
// Unit/MailjetServiceTest.php
test('generates correct email html')
test('sends emails via mailjet api')

// Unit/QualificationStatisticsServiceTest.php
test('calculates kpis correctly')
test('generates temporal statistics')
test('filters data properly')

// Unit/RoleServiceTest.php
test('assigns roles correctly')
test('checks permissions hierarchy')
```

---

## 11. API EXTERNES

### API Apidae

**Endpoint** : `https://api.apidae-tourisme.com/api/v002/recherche/list-objets-touristiques`

**Configuration** :
```env
APIDAE_API_KEY=your_api_key
APIDAE_PROJECT_ID=your_project_id
APIDAE_SELECTION_ID=your_selection_id
```

**Requête Type** :
```php
[
    'apiKey' => config('services.apidae.api_key'),
    'projetId' => config('services.apidae.project_id'),
    'selectionIds' => [config('services.apidae.selection_id')],
    'count' => 20,
    'first' => $offset,
    'order' => 'IDENTIFIANT',
    'asc' => true
]
```

**Données Récupérées** :
- `identifiant` → apidae_id
- `nom.libelleFr` → name
- `localisation.adresse.commune.nom` → city
- `informations.moyensCommunication[].coordonnees.fr` → email/phone/website
- `presentation.descriptifCourt.libelleFr` → description

### API Mailjet

**Version** : v3.1

**SDK** : mailjet/mailjet-apiv3-php

**Configuration** :
```env
MAILJET_APIKEY=your_api_key
MAILJET_APISECRET=your_api_secret
```

**Templates Emails** :
- `emails/availability-request.blade.php`
- `emails/user-approved.blade.php`

**Envoi Type** :
```php
$response = $mj->post(Resources::$Email, ['body' => [
    'Messages' => [[
        'From' => ['Email' => $from, 'Name' => $fromName],
        'To' => [['Email' => $to, 'Name' => $toName]],
        'Subject' => $subject,
        'HTMLPart' => $html,
        'TextPart' => $text
    ]]
]]);
```

---

## 12. ASSETS ET RESSOURCES

### Build Process

**Tool** : Vite 6.0

**Configuration** : `vite.config.js`
```javascript
export default {
    plugins: [
        laravel(['resources/css/app.css', 'resources/js/app.js']),
        tailwindcss()
    ],
    server: {
        cors: true
    }
}
```

**Commandes** :
```bash
npm run dev    # Développement avec HMR
npm run build  # Production build
```

### Structure Assets

```
resources/
├── css/
│   └── app.css          # Tailwind directives
├── js/
│   └── app.js           # JavaScript principal
└── views/
    ├── components/      # Composants Blade réutilisables
    ├── emails/          # Templates emails
    ├── livewire/        # Vues Livewire
    ├── qualification/   # Vues module qualification
    └── layouts/         # Layouts principaux
```

### Tailwind Configuration

**Version** : 4.0.7

**Plugins** :
- DaisyUI 5.0.43

**Config** : `tailwind.config.js`
```javascript
module.exports = {
    content: [
        './resources/**/*.blade.php',
        './resources/**/*.js',
    ],
    plugins: [require('daisyui')],
}
```

---

## 13. DOCUMENTATION

### Fichiers Documentation Existants

| Fichier | Taille | Contenu |
|---------|--------|---------|
| APIDAE_SETUP.md | 4.6 KB | Configuration et utilisation API Apidae |
| APIDAE_SCHEDULING.md | 13.5 KB | Planification tâches automatiques |
| MAILJET_SETUP.md | 14.4 KB | Configuration service email |
| Outil-qualification.md | 1.3 KB | Description module qualification |
| clearcache.md | 173 B | Commandes nettoyage cache |

### Documentation Manquante

- ❌ README.md principal
- ❌ Guide déploiement
- ❌ Documentation API
- ❌ Guide contribution

### Documentation Recommandée

```markdown
# README.md
- Description projet
- Requirements
- Installation
- Configuration
- Usage
- Testing
- Deployment

# docs/API.md
- Endpoints
- Authentication
- Responses
- Examples

# docs/DEPLOYMENT.md
- Server requirements
- Environment setup
- Database migration
- Queue configuration
- Cron jobs
```

---

## 14. PROBLÈMES ET AMÉLIORATIONS

### Problèmes Identifiés

#### 🔴 Critiques

1. **Tests Manquants**
   - 0% coverage
   - Risque régression élevé
   - Pas de CI/CD

2. **Scheduler Non Configuré**
   - Documentation présente mais non implémentée
   - Fetch Apidae manuel uniquement

3. **Tokens Sans Expiration**
   - Tokens accommodation response permanents
   - Risque sécurité

#### 🟡 Importants

4. **Performance SQLite**
   - Limites pour volumétrie importante
   - Pas de cache Redis

5. **Gestion Erreurs Basique**
   - Logs non structurés
   - Pas de monitoring

6. **UX Limitée**
   - Pas de notifications temps réel
   - Loading states absents
   - Breadcrumbs manquants

### Plan d'Amélioration

#### Phase 1 : Sécurité & Tests (Priorité haute)

```
Semaine 1-2:
├─ Écrire tests critiques (auth, permissions)
├─ Ajouter TTL tokens (24h)
├─ Implémenter rate limiting
└─ Configurer CI/CD basique

Semaine 3-4:
├─ Tests features complètes
├─ Audit sécurité complet
├─ 2FA pour admins
└─ Logs structurés
```

#### Phase 2 : Performance & Fiabilité

```
Semaine 5-6:
├─ Migration MySQL production
├─ Cache Redis
├─ Queue supervisée
└─ Monitoring (Sentry)

Semaine 7-8:
├─ Optimisation requêtes
├─ Eager loading
├─ Pagination API
└─ CDN assets
```

#### Phase 3 : Expérience Utilisateur

```
Semaine 9-10:
├─ Notifications temps réel (Pusher)
├─ Loading states animés
├─ Breadcrumbs navigation
└─ Dark mode complet

Semaine 11-12:
├─ Progressive Web App
├─ Offline mode
├─ Export multi-formats
└─ Dashboard analytics
```

### Estimations Budgétaires

| Phase | Durée | Coût Estimé | ROI |
|-------|-------|-------------|-----|
| Sécurité & Tests | 4 semaines | 8-12k€ | Critique |
| Performance | 4 semaines | 6-10k€ | Élevé |
| UX | 4 semaines | 5-8k€ | Moyen |

---

## 15. POINTS FORTS

### Architecture

✅ **Laravel 12** - Framework moderne et maintenu
✅ **Livewire** - Réactivité sans JavaScript complexe
✅ **Tailwind CSS 4** - Styles utility-first
✅ **Structure MVC** - Code organisé et maintenable

### Fonctionnalités

✅ **Multi-modules** - Séparation claire des domaines
✅ **Permissions granulaires** - Contrôle d'accès fin
✅ **Formulaires multi-étapes** - UX progressive
✅ **Statistiques avancées** - Insights temps réel

### Intégrations

✅ **API Apidae** - Données touristiques à jour
✅ **Mailjet** - Emails transactionnels fiables
✅ **Queue asynchrone** - Performance optimisée
✅ **Export Excel** - Exploitation données facilitée

### Code Quality

✅ **Services isolés** - Logique métier centralisée
✅ **Validation robuste** - Données cohérentes
✅ **Migrations versionnées** - Évolution BDD tracée
✅ **Configuration externalisée** - Déploiement flexible

---

## 16. CONCLUSION

### Synthèse Générale

**dispo-app** est une application web Laravel 12 professionnelle qui répond efficacement aux besoins de gestion touristique avec deux modules complémentaires :

1. **Module Disponibilités** : Automatisation de la collecte des disponibilités d'hébergements via API Apidae et emails interactifs
2. **Module Qualification** : Collecte structurée de données visiteurs avec formulaires intelligents

### Forces Principales

- ✅ Architecture moderne et scalable
- ✅ Sécurité multi-niveaux (auth, permissions, validation)
- ✅ UX pensée avec Livewire (réactivité, multi-étapes)
- ✅ Intégrations externes robustes
- ✅ Code métier bien organisé

### Points d'Amélioration Prioritaires

- ⚠️ Tests automatisés absents
- ⚠️ Scheduler non configuré
- ⚠️ Tokens sans expiration
- ⚠️ Performance SQLite limitée

### Recommandations

**Court terme** : Sécuriser avec tests et TTL tokens
**Moyen terme** : Optimiser avec MySQL et cache Redis
**Long terme** : Enrichir UX avec temps réel et PWA

### Verdict

Application **production-ready** avec réserves sur les tests. Architecture solide permettant une évolution sereine. Investissement dans les tests et optimisations recommandé pour un déploiement à grande échelle.

---

*Document généré le 17 novembre 2025*
*Application dispo-app v1.0*
*Laravel 12.0 | PHP 8.2 | Livewire 3.x*