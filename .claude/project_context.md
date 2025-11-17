# Contexte du Projet DISPO-APP

## Vue d'ensemble
Application web Laravel 12 de gestion touristique avec deux modules principaux :
- **Module Disponibilités** : Gestion des hébergements via API Apidae
- **Module Qualification** : Collecte de données visiteurs pour 5 villes touristiques

## Documentation Principale
📄 **[Analyse complète de l'application](../ANALYSE_APPLICATION.md)** - Document exhaustif de 1600+ lignes avec tous les détails techniques

## Stack Technique
- **Backend** : Laravel 12, PHP 8.2+
- **Frontend** : Livewire 3.x, Tailwind CSS 4, DaisyUI 5
- **Base de données** : SQLite (dev) / MySQL (prod)
- **Queue** : Database driver
- **APIs** : Apidae (hébergements), Mailjet (emails)

## Architecture Clé
```
app/
├── Livewire/          # 20+ composants réactifs
├── Models/            # User, Accommodation, Qualification
├── Services/          # Mailjet, Statistics, Roles, Geography
├── Console/Commands/  # FetchApidaeData, SendEmails
└── Jobs/              # SendAccommodationEmail, SendApprovalEmail
```

## Système de Permissions
- **Super-admin** : Accès total + gestion utilisateurs
- **Admin** : Accès total sauf gestion utilisateurs
- **Qualification** : Gestion module qualification
- **Disponibilites** : Gestion module hébergements
- **Utilisateurs** : Formulaires uniquement

## Points d'Attention
⚠️ **Tests** : 0% coverage - À implémenter en priorité
⚠️ **Scheduler** : Non configuré - Documentation dans APIDAE_SCHEDULING.md
⚠️ **Tokens** : Sans expiration - Ajouter TTL
⚠️ **Base de données** : SQLite en prod - Migrer vers MySQL

## Commandes Utiles
```bash
# Récupérer hébergements Apidae
php artisan apidae:fetch --all

# Lancer les tests (à écrire)
php artisan test

# Lancer la queue
php artisan queue:work

# Clear cache
php artisan cache:clear
php artisan view:clear
php artisan config:clear
```

## Fichiers de Configuration
- `.env` : Variables d'environnement (API keys, DB, Mail)
- `config/services.php` : Configuration Mailjet
- `config/french_geography.php` : Données géographiques France
- `config/permission.php` : Configuration Spatie

## Documentation Disponible
- `ANALYSE_APPLICATION.md` : Analyse technique complète
- `APIDAE_SETUP.md` : Configuration API Apidae
- `APIDAE_SCHEDULING.md` : Planification automatique
- `MAILJET_SETUP.md` : Configuration emails
- `Outil-qualification.md` : Description module qualification

## Modules Métier

### Module Disponibilités
- Récupération hébergements via API Apidae
- Envoi emails de sollicitation (Mailjet)
- Tracking réponses avec tokens uniques
- Statuts : en_attente, disponible, indisponible

### Module Qualification
- Formulaire multi-étapes (3 étapes)
- 5 villes touristiques
- Statistiques avancées (Chart.js)
- Export Excel
- Sauvegarde brouillon automatique

---

**Pour tout développement, consultez d'abord [ANALYSE_APPLICATION.md](../ANALYSE_APPLICATION.md) pour comprendre l'architecture existante.**