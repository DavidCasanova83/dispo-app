# Instructions pour le développement

## Avant de commencer
1. Lire `project_context.md` pour comprendre l'architecture
2. Consulter `ANALYSE_APPLICATION.md` pour les détails techniques
3. Vérifier les permissions et rôles nécessaires pour la fonctionnalité

## Conventions de code

### Backend (Laravel)
- Utiliser les Services pour la logique métier
- Respecter le pattern Repository si nécessaire
- Valider toutes les entrées utilisateur
- Utiliser les Jobs pour les tâches longues
- Respecter les conventions PSR-12

### Frontend (Livewire)
- Créer des composants réutilisables
- Utiliser les propriétés réactives (`wire:model`)
- Validation temps réel côté Livewire
- Émission d'événements pour la communication entre composants

### Base de données
- Toujours créer des migrations
- Utiliser les index pour les colonnes fréquemment requêtées
- Relations Eloquent plutôt que joins manuels
- Soft deletes si suppression logique nécessaire

### Sécurité
- Vérifier les permissions avec `@can()` ou `->middleware(['permission:'])`
- Échapper les sorties avec `{{ }}` (jamais `{!! !!}` sauf HTML sûr)
- Valider côté serveur même si validation côté client
- Utiliser les policies pour les autorisations complexes

## Workflow de développement

### Pour ajouter une nouvelle fonctionnalité
1. **Analyser l'existant** : Vérifier dans `ANALYSE_APPLICATION.md`
2. **Créer les migrations** si nécessaire
3. **Créer/modifier le modèle** Eloquent
4. **Créer le service** pour la logique métier
5. **Créer le composant Livewire** ou contrôleur
6. **Ajouter les routes** avec les bonnes permissions
7. **Créer les vues** Blade
8. **Ajouter les tests** (priorité haute)
9. **Documenter** les changements

### Permissions requises par module

#### Module Disponibilités
- `view-disponibilites` : Consultation
- `edit-disponibilites` : Modification

#### Module Qualification
- `view-qualification` : Consultation et statistiques
- `edit-qualification` : Modification des données
- `fill-forms` : Remplissage formulaires

#### Administration
- `manage-users` : Gestion utilisateurs (Super-admin uniquement)

## Commandes fréquentes

```bash
# Créer un composant Livewire
php artisan make:livewire NomComposant

# Créer une migration
php artisan make:migration create_table_name

# Créer un modèle avec migration
php artisan make:model ModelName -m

# Créer un service
# Créer manuellement dans app/Services/

# Créer un job
php artisan make:job JobName

# Créer une commande
php artisan make:command CommandName

# Clear tous les caches
php artisan cache:clear && php artisan view:clear && php artisan config:clear

# Relancer les migrations (DEV uniquement)
php artisan migrate:fresh --seed
```

## Structure des composants Livewire

```php
class MonComposant extends Component
{
    // Propriétés publiques (accessibles dans la vue)
    public $search = '';
    public $items = [];

    // Validation
    protected $rules = [
        'search' => 'required|min:3',
    ];

    // Listeners d'événements
    protected $listeners = ['refreshComponent' => '$refresh'];

    // Query string (pour persistence URL)
    protected $queryString = ['search'];

    // Cycle de vie
    public function mount() { }
    public function updated($propertyName) { }

    // Méthodes
    public function search() { }

    // Rendu
    public function render()
    {
        return view('livewire.mon-composant');
    }
}
```

## Bonnes pratiques

### Performance
- Utiliser `with()` pour eager loading
- Paginer les grandes listes (100 items max)
- Utiliser le cache pour les données statiques
- Queue pour les tâches > 2 secondes

### UX
- Loading states pour les actions longues
- Messages de confirmation/erreur clairs
- Validation temps réel
- Breadcrumbs pour la navigation

### Maintenance
- Commenter le code complexe
- Logger les actions importantes
- Documenter les changements
- Garder les méthodes courtes (< 20 lignes)

## Points d'attention actuels
1. **Pas de tests** - En créer systématiquement
2. **Scheduler non configuré** - Voir APIDAE_SCHEDULING.md
3. **Tokens sans TTL** - Ajouter expiration
4. **SQLite en prod** - Prévoir migration MySQL

## Contacts et ressources
- Documentation Laravel : https://laravel.com/docs
- Documentation Livewire : https://livewire.laravel.com
- Documentation Spatie Permission : https://spatie.be/docs/laravel-permission