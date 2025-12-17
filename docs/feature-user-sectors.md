# Feature: Assignation de secteurs aux utilisateurs

**Date:** 2025-12-17
**Branche:** feature/content-audit

## Description

Cette fonctionnalite permet d'assigner un ou plusieurs secteurs a un utilisateur depuis la page d'administration `/admin/users`. La selection des secteurs est integree dans la modal "Gerer les roles et secteurs".

---

## Modifications apportees

### 1. Base de donnees

#### Nouvelle migration: `2025_12_17_105643_create_sector_user_table.php`

Table pivot pour la relation many-to-many entre `users` et `sectors`.

```php
Schema::create('sector_user', function (Blueprint $table) {
    $table->id();
    $table->foreignId('sector_id')->constrained()->onDelete('cascade');
    $table->foreignId('user_id')->constrained()->onDelete('cascade');
    $table->timestamps();
    $table->unique(['sector_id', 'user_id']);
});
```

**Caracteristiques:**
- Suppression en cascade si un secteur ou utilisateur est supprime
- Contrainte d'unicite pour eviter les doublons

---

### 2. Modeles

#### `app/Models/Sector.php`

Ajout de la relation `users()`:

```php
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

public function users(): BelongsToMany
{
    return $this->belongsToMany(User::class)->withTimestamps();
}
```

#### `app/Models/User.php`

Ajout de la relation `sectors()`:

```php
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

public function sectors(): BelongsToMany
{
    return $this->belongsToMany(Sector::class)->withTimestamps();
}
```

---

### 3. Composant Livewire

#### `app/Livewire/Admin/UserRoles.php`

**Nouvelles proprietes:**
```php
public $selectedSectors = [];
public $availableSectors = [];
```

**Modifications de `mount()`:**
```php
$this->user = User::with(['roles', 'sectors'])->findOrFail($userId);
$this->availableSectors = Sector::orderBy('name')->get();
$this->selectedSectors = $this->user->sectors->pluck('id')->toArray();
```

**Nouvelle methode `toggleSector()`:**
```php
public function toggleSector($sectorId)
{
    $sectorId = (int) $sectorId;
    if (in_array($sectorId, $this->selectedSectors)) {
        $this->selectedSectors = array_values(array_diff($this->selectedSectors, [$sectorId]));
    } else {
        $this->selectedSectors[] = $sectorId;
    }
}
```

**Modification de `save()`:**
```php
// Sync sectors
$this->user->sectors()->sync($this->selectedSectors);
```

---

### 4. Vue Blade

#### `resources/views/livewire/admin/user-roles.blade.php`

**Titre modifie:**
```html
<h3>Gerer les roles et secteurs</h3>
```

**Nouvelle section pour les secteurs** (ajoutee au-dessus des roles):

- Grille 2 colonnes de checkboxes
- Style vert pour les secteurs selectionnes (`border-green-500`, `bg-green-50`)
- Icone de validation pour les secteurs selectionnes
- Message "Aucun secteur disponible" si la liste est vide

---

## Structure des fichiers modifies

```
dispo-app/
├── app/
│   ├── Livewire/Admin/
│   │   └── UserRoles.php              # Logique composant
│   └── Models/
│       ├── Sector.php                 # Relation users()
│       └── User.php                   # Relation sectors()
├── database/migrations/
│   └── 2025_12_17_105643_create_sector_user_table.php
└── resources/views/livewire/admin/
    └── user-roles.blade.php           # Interface utilisateur
```

---

## Utilisation

1. Acceder a `/admin/users`
2. Cliquer sur "Gerer les roles" pour un utilisateur
3. La modal affiche maintenant:
   - **Secteurs assignes** (en haut) - checkboxes vertes
   - **Roles disponibles** (en bas) - checkboxes bleues
4. Selectionner les secteurs souhaites
5. Cliquer sur "Enregistrer"

---

## Acces aux secteurs d'un utilisateur

```php
// Recuperer les secteurs d'un utilisateur
$user->sectors;

// Recuperer les utilisateurs d'un secteur
$sector->users;

// Verifier si un utilisateur a un secteur specifique
$user->sectors->contains('id', $sectorId);
```
