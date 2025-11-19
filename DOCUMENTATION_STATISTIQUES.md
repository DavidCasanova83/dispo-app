# Documentation Technique - Système de Statistiques des Qualifications

## Vue d'ensemble

Le système de statistiques permet d'analyser en profondeur les données de qualification de l'Office de Tourisme Intercommunal (OTI) Verdon Tourisme. Il offre une interface interactive avec filtres temporels, visualisations graphiques multiples et export de données.

### Architecture du système

Le système est composé de 4 fichiers principaux qui interagissent selon une architecture MVC/Service :

```
┌─────────────────────────────────────────────────────────────┐
│                    Interface Utilisateur                     │
│            statistics-v2.blade.php (Vue)                     │
│    - Filtres de période                                      │
│    - Cartes KPI                                              │
│    - Graphiques Chart.js                                     │
│    - Modal d'export                                          │
└───────────────────┬─────────────────────────────────────────┘
                    │
                    ↓
┌─────────────────────────────────────────────────────────────┐
│              QualificationStatisticsV2                       │
│           (Composant Livewire - Contrôleur)                 │
│    - Gestion des filtres de période                         │
│    - Coordination entre vue et service                       │
│    - Export de données                                       │
└───────────────────┬─────────────────────────────────────────┘
                    │
        ┌───────────┴───────────┐
        ↓                       ↓
┌──────────────────┐   ┌──────────────────┐
│ Statistics       │   │ Qualifications   │
│ Service          │   │ Export           │
│                  │   │                  │
│ - Calculs KPI    │   │ - Export Excel   │
│ - Agrégations    │   │ - Formatting     │
│ - Analyses       │   │ - Filtres        │
└──────────────────┘   └──────────────────┘
```

---

## 1. QualificationStatisticsV2.php (Composant Livewire)

**Localisation :** `app/Livewire/QualificationStatisticsV2.php`

### Responsabilités

Ce composant Livewire agit comme contrôleur principal :
- Gère l'état des filtres de période
- Coordonne les appels au service de statistiques
- Gère l'export de données
- Transmet les données à la vue

### Propriétés publiques

```php
public $selectedPeriod = '30days';  // Période sélectionnée par l'utilisateur
public $periodFilter = '30days';    // Période actuellement appliquée aux données
```

**Distinction importante :**
- `selectedPeriod` : Valeur du formulaire (binding live)
- `periodFilter` : Période réellement appliquée (après validation)

### Méthodes principales

#### `applyFilter()`

**Déclenchement :** Lorsque l'utilisateur sélectionne une nouvelle période

**Fonctionnement :**
1. Copie `selectedPeriod` → `periodFilter`
2. Récupère les nouvelles données via `getStatisticsData()`
3. Émet un événement `statistics-updated` pour mettre à jour les graphiques dynamiquement

```php
public function applyFilter()
{
    $this->periodFilter = $this->selectedPeriod;
    $this->dispatch('statistics-updated', statistics: $this->getStatisticsData());
}
```

#### `getStatisticsData()`

**Responsabilité :** Collecte toutes les statistiques selon le filtre appliqué

**Processus :**

1. **Initialisation du service**
   ```php
   $service = new QualificationStatisticsService();
   ```

2. **Récupération des villes**
   ```php
   $cities = array_keys(Qualification::getCities());
   ```

3. **Conversion de la période en dates**

   Périodes disponibles :
   - `7days` : 7 derniers jours
   - `30days` : 30 derniers jours (par défaut)
   - `90days` : 90 derniers jours
   - `180days` : 180 derniers jours
   - `all` : Toutes les données (startDate et endDate = null)

4. **Appels au service pour chaque type de statistique**
   ```php
   return [
       'kpis' => $service->getKPIs(...),
       'cityStats' => $service->getStatsByCity(...),
       'temporalEvolution' => $service->getTemporalEvolution(...),
       'geographic' => $service->getGeographicStats(...),
       'profiles' => $service->getProfileStats(...),
       'demands' => $service->getDemandStats(...),
       'contact' => $service->getContactStats(...)
   ];
   ```

#### `getGroupBy()`

**Responsabilité :** Détermine le niveau de granularité temporelle

Actuellement retourne toujours `'day'`, mais peut être étendu pour :
- `hour` : Groupement par heure
- `day` : Groupement par jour
- `week` : Groupement par semaine
- `month` : Groupement par mois

#### `exportData($startDate, $endDate)`

**Responsabilité :** Générer un export Excel personnalisé

**Processus :**

1. **Validation des dates**
   - Parse les dates avec Carbon
   - Vérifie que startDate ≤ endDate
   - Gestion des erreurs avec événements Livewire

2. **Préparation des filtres**
   ```php
   $filters = [
       'startDate' => $startDate,
       'endDate' => $endDate,
       'status' => 'all'
   ];
   ```

3. **Génération du fichier**
   - Nom de fichier : `qualifications_DD-MM-YYYY_au_DD-MM-YYYY.xlsx`
   - Utilise `QualificationsExport` avec les filtres
   - Retourne un `BinaryFileResponse` pour téléchargement

**Gestion d'erreurs :**
```php
if ($start->greaterThan($end)) {
    $this->dispatch('export-error', message: '...');
    return response()->download('');
}
```

#### `render()`

**Responsabilité :** Prépare et retourne la vue

```php
return view('livewire.qualification.statistics-v2', [
    'cities' => Qualification::getCities(),
    'statistics' => $this->getStatisticsData()
]);
```

---

## 2. QualificationStatisticsService.php (Service de calcul)

**Localisation :** `app/Services/QualificationStatisticsService.php`

Ce service contient toute la logique métier pour calculer les statistiques. Il utilise Eloquent et Query Builder pour des requêtes optimisées.

### Méthode utilitaire : `baseQuery()`

**Responsabilité :** Créer une requête de base avec filtres communs

**Filtres appliqués :**
- Villes (optionnel)
- Date de début (optionnel)
- Date de fin (optionnel)
- Statut : `all`, `completed`, `incomplete`

```php
protected function baseQuery(array $cities = [], $startDate = null, $endDate = null, $status = 'all')
{
    $query = Qualification::query();

    if (!empty($cities)) {
        $query->whereIn('city', $cities);
    }

    if ($startDate) {
        $query->where('created_at', '>=', Carbon::parse($startDate)->startOfDay());
    }

    if ($endDate) {
        $query->where('created_at', '<=', Carbon::parse($endDate)->endOfDay());
    }

    if ($status === 'completed') {
        $query->where('completed', true);
    } elseif ($status === 'incomplete') {
        $query->where('completed', false);
    }

    return $query;
}
```

### 1. `getKPIs()` - Indicateurs clés de performance

**Retourne :**
```php
[
    'total' => 150,              // Nombre total de qualifications
    'completed' => 120,          // Nombre complétées
    'incomplete' => 30,          // Nombre incomplètes
    'completionRate' => 80.0,    // Taux de complétion (%)
    'today' => 5,                // Qualifications aujourd'hui
    'thisWeek' => 25,            // Qualifications cette semaine
    'growth' => 15.5             // Croissance par rapport à période précédente (%)
]
```

**Calcul de la croissance :**

1. Calcule la durée de la période actuelle
2. Détermine la période précédente équivalente
3. Compare les nombres
4. Formule : `((current - previous) / previous) * 100`

**Exemple :**
- Période actuelle : 1er-30 mars → 100 qualifications
- Période précédente : 1er-28 février → 80 qualifications
- Croissance : ((100 - 80) / 80) * 100 = +25%

### 2. `getStatsByCity()` - Statistiques par ville et utilisateur

**Retourne :**
```php
[
    'castellane' => [
        'name' => 'Castellane',
        'total' => 50,
        'byUser' => [
            ['user_name' => 'Marie Dupont', 'count' => 30],
            ['user_name' => 'Jean Martin', 'count' => 20]
        ]
    ],
    'moustiers' => [...]
]
```

**Utilisation :** Graphique en barres empilées montrant la contribution de chaque agent par ville

**Particularité :** Ne compte que les qualifications **complétées**

### 3. `getTemporalEvolution()` - Évolution temporelle

**Responsabilité :** Suivre l'évolution des qualifications dans le temps

**Compatibilité multi-base de données :**

Le code détecte automatiquement le driver de base de données et adapte les fonctions de formatage de date :

- **SQLite** : utilise `strftime()`
- **MySQL/MariaDB** : utilise `DATE_FORMAT()`

**Groupements supportés :**
- `hour` : Par heure (`%Y-%m-%d %H:00:00`)
- `day` : Par jour (`%Y-%m-%d`)
- `week` : Par semaine (`%Y-%W` ou `%Y-%u`)
- `month` : Par mois (`%Y-%m`)
- `year` : Par année (`%Y`)

**Retourne (selon le contexte) :**

Si `$cities` est vide (données globales) :
```php
[
    'global' => [
        ['period' => '2024-03-01', 'count' => 10],
        ['period' => '2024-03-02', 'count' => 15],
        ...
    ]
]
```

Si `$cities` contient des villes (données par ville) :
```php
[
    'castellane' => [
        ['period' => '2024-03-01', 'count' => 5],
        ['period' => '2024-03-02', 'count' => 8],
    ],
    'moustiers' => [...]
]
```

### 4. `getGeographicStats()` - Statistiques géographiques

**Retourne :**
```php
[
    'countries' => [
        'France' => 120,
        'Belgique' => 15,
        'Allemagne' => 10,
        ...  // Top 10
    ],
    'departments' => [
        '04' => 80,
        '06' => 25,
        '13' => 15,
        ...  // Top 10
    ]
]
```

**Logique spéciale pour les pays :**
```php
$country = $q->form_data['country'] ?? null;
if ($country === 'Autre' && isset($q->form_data['otherCountry'])) {
    return $q->form_data['otherCountry'];  // Utilise le pays saisi manuellement
}
```

**Logique pour les départements :**
- Ne compte que les visiteurs de France
- Exclut ceux ayant coché "Département inconnu"
- Utilise `flatMap()` car un visiteur peut sélectionner plusieurs départements

### 5. `getProfileStats()` - Profils des visiteurs

**Retourne :**
```php
[
    'profiles' => [
        'Touriste' => 80,
        'Résident local' => 30,
        'Professionnel' => 20,
        ...
    ],
    'ageGroups' => [
        '18-25 ans' => 25,
        '26-35 ans' => 40,
        '36-50 ans' => 35,
        ...
    ]
]
```

**Particularité des tranches d'âge :**
Utilise `flatMap()` car le formulaire permet la sélection multiple de tranches d'âge.

### 6. `getDemandStats()` - Statistiques des demandes

**Retourne :**
```php
[
    'generalRequests' => [
        'Hébergement' => 50,
        'Restauration' => 35,
        ...  // Top 10
    ],
    'specificRequests' => [
        'castellane' => [
            'Randonnée' => 30,
            'Visites guidées' => 20,
            ...
        ],
        'moustiers' => [...]
    ],
    'topSpecificRequests' => [
        'Randonnée' => 60,
        'Visites guidées' => 45,
        ...  // Top 10 toutes villes confondues
    ],
    'otherSpecificRequests' => [
        'Activités nautiques' => 25,
        ...  // Top 10 des demandes croisées
    ],
    'otherRequests' => [
        'Je cherche un hébergement avec vue sur le lac',
        'Informations sur les sentiers de randonnée',
        ...  // Textes libres (max 20 affichés dans la vue)
    ]
]
```

**Structure complexe car :**
- Demandes générales : communes à toutes les villes
- Demandes spécifiques : propres à chaque ville
- Demandes croisées : quand un visiteur cherche des services dans plusieurs villes
- Textes libres : saisie libre par le visiteur

### 7. `getContactStats()` - Statistiques de contact

**Retourne :**
```php
[
    'emailProvided' => 80,        // Nombre d'emails fournis
    'emailRate' => 53.3,          // Pourcentage (80/150 * 100)
    'newsletterAccepted' => 60,   // Nombre ayant accepté la newsletter
    'newsletterRate' => 75.0,     // Pourcentage parmi ceux ayant fourni email (60/80 * 100)
    'contactMethods' => [
        'Email' => 80,
        'Téléphone' => 50,
        'Courrier' => 20
    ]
]
```

**Taux de newsletter :**
Calculé **uniquement parmi ceux qui ont fourni un email**, pas sur le total.

---

## 3. QualificationsExport.php (Export Excel)

**Localisation :** `app/Exports/QualificationsExport.php`

### Interfaces implémentées (Laravel Excel)

```php
class QualificationsExport implements
    FromQuery,           // Utilise une query Eloquent
    WithHeadings,        // Ajoute des en-têtes
    WithMapping,         // Map les données
    WithStyles,          // Applique des styles
    ShouldAutoSize,      // Auto-ajuste les colonnes
    WithEvents,          // Événements (formatage avancé)
    WithChunkReading     // Traitement par lots (performances)
```

### Méthodes principales

#### `query()`

Construit la requête Eloquent pour l'export.

**Filtres appliqués :**
1. Villes (si spécifiées)
2. Période (startDate / endDate)
3. Statut (completed / incomplete / all)

**Optimisation :** Utilise `with('user')` pour éviter le problème N+1

```php
$query = Qualification::with('user');
// ... application des filtres
return $query->orderBy('created_at', 'desc');
```

#### `chunkSize()`

Traite 500 enregistrements à la fois pour économiser la mémoire sur de gros exports.

#### `headings()`

Définit les 22 colonnes du fichier Excel :

1. ID
2. Ville
3. Statut
4. Date de création
5. Date de complétion
6. Étape actuelle
7. Nom de l'agent
8. Email de l'agent
9. Pays
10. Département(s)
11. Département inconnu
12. Email visiteur
13. Consent Newsletter
14. Consent Traitement données
15. Profil visiteur
16. Tranches d'âge
17. Date de modification
18. Méthode de contact
19. Demandes spécifiques ville
20. Autres demandes spécifiques
21. Demandes générales
22. Demande texte libre

#### `map($qualification)`

Transforme chaque objet `Qualification` en tableau pour Excel.

**Transformations importantes :**

1. **Conversion des tableaux en chaînes**
   ```php
   $departments = implode(', ', $formData['departments']);
   $ageGroups = implode(', ', $formData['ageGroups']);
   ```

2. **Formatage des booléens**
   ```php
   ($formData['consentNewsletter'] ?? false) ? 'Oui' : 'Non'
   ```

3. **Formatage des dates**
   ```php
   $qualification->created_at->format('d/m/Y H:i')
   ```

4. **Récupération du nom de ville**
   ```php
   $cityName = Qualification::getCities()[$qualification->city] ?? $qualification->city;
   ```

#### `styles(Worksheet $sheet)`

Applique le style de base : en-têtes en gras.

#### `registerEvents()`

Formatage avancé après génération de la feuille :

1. **Filtre automatique** sur toutes les colonnes (A1:V1)
2. **Gel de la première ligne** pour garder les en-têtes visibles
3. **Couleur de fond des en-têtes** : #3E9B90 (vert turquoise)
4. **Texte des en-têtes** : blanc et gras

```php
AfterSheet::class => function(AfterSheet $event) {
    $event->sheet->getDelegate()->setAutoFilter('A1:V1');
    $event->sheet->getDelegate()->freezePane('A2');
    $event->sheet->getDelegate()->getStyle('A1:V1')->applyFromArray([...]);
}
```

---

## 4. statistics-v2.blade.php (Vue et Graphiques)

**Localisation :** `resources/views/livewire/qualification/statistics-v2.blade.php`

### Structure de la vue

```
┌─────────────────────────────────────────┐
│ Header + Bouton Export                  │
├─────────────────────────────────────────┤
│ Filtres de période (radio buttons)     │
├─────────────────────────────────────────┤
│ Modal Export (Alpine.js)               │
├─────────────────────────────────────────┤
│ KPIs (4 cartes)                         │
├─────────────────────────────────────────┤
│ Graphiques (si données disponibles)    │
│ - Évolution temporelle                  │
│ - Comparatif villes / Pays              │
│ - Profils / Âges                        │
│ - Demandes générales                    │
│ - Top demandes spécifiques / Départements│
│ - Demandes par ville                    │
│ - Méthodes contact / Emails             │
│ - Demandes textuelles                   │
└─────────────────────────────────────────┘
```

### Filtres de période

**Implémentation Livewire :**

```blade
<input type="radio"
       wire:model.live="selectedPeriod"
       wire:change="applyFilter"
       value="7days">
```

- `wire:model.live` : Mise à jour automatique de la propriété
- `wire:change="applyFilter"` : Appelle la méthode au changement

**5 options disponibles :**
- 7 derniers jours
- 30 derniers jours
- 90 derniers jours
- 180 derniers jours
- Toutes les données

### Modal d'export (Alpine.js)

**État géré par Alpine.js :**

```javascript
{
    open: false,
    startDate: '',
    endDate: '',
    error: '',
    loading: false,
    startPicker: null,
    endPicker: null
}
```

**Fonctionnalités :**

1. **Sélecteurs de dates Flatpickr**
   - Format : `d/m/Y`
   - Locale : français
   - Max date : aujourd'hui
   - Lien entre les deux : la date de fin ne peut être avant la date de début

2. **Validation**
   ```javascript
   validateAndExport() {
       if (!this.startDate || !this.endDate) {
           this.error = 'Veuillez sélectionner...';
           return;
       }
       if (new Date(this.startDate) > new Date(this.endDate)) {
           this.error = 'La date de début...';
           return;
       }
       $wire.exportData(this.startDate, this.endDate);
   }
   ```

3. **Gestion du chargement**
   - Affiche un spinner pendant l'export
   - Désactive les boutons

### Cartes KPI

**4 indicateurs affichés :**

1. **Total Qualifications** (bleu)
2. **Taux de complétion** (vert)
3. **Cette semaine** (violet)
4. **Croissance** (vert si positif, rouge si négatif)

**Exemple de carte :**

```blade
<div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm p-6">
    <div class="flex items-center justify-between">
        <div>
            <p class="text-sm font-medium text-gray-600">Total Qualifications</p>
            <p class="text-3xl font-bold text-gray-900 mt-2">
                {{ number_format($statistics['kpis']['total']) }}
            </p>
        </div>
        <div class="p-3 bg-blue-100 rounded-full">
            <svg><!-- Icône --></svg>
        </div>
    </div>
</div>
```

### Graphiques Chart.js

**Bibliothèques chargées :**
- Chart.js 4.4.0
- chartjs-adapter-date-fns (pour les dates)
- Flatpickr (sélecteurs de dates)

#### Gestion globale des graphiques

**Registre des instances :**
```javascript
let chartInstances = {};
```

**Fonction de destruction :**
```javascript
function destroyAllCharts() {
    Object.keys(chartInstances).forEach(key => {
        if (chartInstances[key]) {
            chartInstances[key].destroy();
        }
    });
    chartInstances = {};
}
```

**Importance :** Évite les fuites mémoire lors des mises à jour de filtres.

#### Configuration globale

**Détection du mode sombre :**
```javascript
const isDark = document.documentElement.classList.contains('dark');
const textColor = isDark ? '#E5E7EB' : '#1F2937';
const gridColor = isDark ? '#374151' : '#E5E7EB';
```

**Palette de couleurs :**
```javascript
const colors = [
    '#3E9B90',  // Turquoise (couleur principale)
    '#F59E0B',  // Orange
    '#EF4444',  // Rouge
    '#8B5CF6',  // Violet
    '#10B981',  // Vert
    '#06B6D4',  // Cyan
    '#EC4899',  // Rose
    '#F97316'   // Orange vif
];
```

#### Types de graphiques

##### 1. Évolution temporelle (Line chart)

```javascript
type: 'line'
options: {
    scales: {
        x: {
            type: 'time',
            time: { unit: 'day' }
        }
    }
}
```

**Particularité :**
- Utilise l'échelle temporelle de Chart.js
- Détecte automatiquement si données globales ou par ville
- Zone remplie sous la courbe (`fill: true`)

##### 2. Comparatif par ville (Stacked bar chart)

```javascript
type: 'bar'
options: {
    scales: {
        x: { stacked: true },
        y: { stacked: true }
    }
}
```

**Construction complexe des datasets :**

1. Collecte tous les utilisateurs uniques
2. Pour chaque utilisateur, crée un dataset
3. Pour chaque ville, trouve le nombre de qualifications de cet utilisateur

Résultat : barres empilées montrant la contribution de chaque agent.

##### 3. Pays de provenance (Doughnut chart)

```javascript
type: 'doughnut'
```

Top 10 des pays d'origine des visiteurs.

##### 4. Profils visiteurs (Pie chart)

```javascript
type: 'pie'
```

Répartition des profils : touriste, résident, professionnel, etc.

##### 5. Tranches d'âge (Horizontal bar chart)

```javascript
type: 'bar'
options: {
    indexAxis: 'y'  // Barres horizontales
}
```

##### 6. Demandes générales (Horizontal bar chart)

Top 10 des demandes générales.

##### 7. Top demandes spécifiques (Horizontal bar chart)

Top 10 toutes villes confondues.

##### 8. Top départements (Horizontal bar chart)

Top 10 des départements français.

##### 9. Demandes spécifiques par ville

Graphiques dynamiques générés en boucle :

```javascript
citiesToShowJS.forEach(cityKey => {
    if (specificRequests[cityKey] && Object.keys(specificRequests[cityKey]).length > 0) {
        const specificCtx = document.getElementById(`specificRequests_${cityKey}`);
        chartInstances[`specificChart_${cityKey.replace(/-/g, '_')}`] = new Chart(...);
    }
});
```

##### 10. Méthodes de contact (Doughnut chart)

Répartition Email / Téléphone / Courrier.

##### 11. Emails collectés

**Pas un graphique**, juste un grand nombre affiché.

##### 12. Demandes textuelles

Liste des 20 premières demandes en texte libre (scroll possible).

### Système de mise à jour dynamique

**Événement Livewire :**

```javascript
window.addEventListener('statistics-updated', (event) => {
    const newStatistics = event.detail.statistics;
    initCharts(newStatistics);  // Régénère tous les graphiques
});
```

**Flux :**
1. Utilisateur change le filtre de période
2. Livewire appelle `applyFilter()`
3. `dispatch('statistics-updated', statistics: ...)`
4. JavaScript reçoit l'événement
5. Détruit tous les graphiques existants
6. Recrée tous les graphiques avec les nouvelles données

---

## Flux de données complet

### Chargement initial

```
1. Utilisateur accède à la page
   ↓
2. QualificationStatisticsV2::render()
   ↓
3. getStatisticsData() avec period = '30days' (défaut)
   ↓
4. QualificationStatisticsService calcule toutes les stats
   ↓
5. Vue affichée avec données
   ↓
6. JavaScript initCharts() crée les graphiques
```

### Changement de filtre

```
1. Utilisateur sélectionne "7 derniers jours"
   ↓
2. wire:model.live met à jour $selectedPeriod
   ↓
3. wire:change déclenche applyFilter()
   ↓
4. $periodFilter = '7days'
   ↓
5. getStatisticsData() avec nouvelles dates
   ↓
6. dispatch('statistics-updated', statistics: [...])
   ↓
7. JavaScript reçoit l'événement
   ↓
8. destroyAllCharts()
   ↓
9. initCharts(newStatistics)
   ↓
10. Graphiques mis à jour sans rechargement de page
```

### Export de données

```
1. Utilisateur clique "Exporter les données"
   ↓
2. Modal Alpine.js s'ouvre
   ↓
3. Utilisateur sélectionne dates avec Flatpickr
   ↓
4. Clique "Télécharger Excel"
   ↓
5. validateAndExport() en JavaScript
   ↓
6. $wire.exportData(startDate, endDate)
   ↓
7. QualificationStatisticsV2::exportData()
   ↓
8. Validation des dates
   ↓
9. new QualificationsExport($filters)
   ↓
10. Excel::download(...)
    ↓
11. QualificationsExport::query() construit la requête
    ↓
12. Traitement par chunks de 500
    ↓
13. map() transforme chaque qualification
    ↓
14. Formatage Excel (styles, filtres, freeze)
    ↓
15. Fichier téléchargé par le navigateur
```

---

## Fonctionnalité avancée : Toggle Valeurs/Pourcentages

### Vue d'ensemble

Le graphique "Comparatif par ville" dispose d'une fonctionnalité de **normalisation en pourcentages** permettant de comparer équitablement la répartition des agents entre villes, indépendamment du volume total de qualifications.

**Problème résolu :** Lorsqu'une ville a 100 qualifications et une autre en a 20, les barres empilées ne permettent pas de comparer facilement la **distribution interne** de chaque ville.

**Solution :** Un bouton toggle bascule entre :
- **Mode Valeurs** : Nombres absolus (défaut)
- **Mode Pourcentage** : Pourcentages normalisés (chaque ville = 100%)

### Code HTML - Bouton Toggle

**Localisation :** `resources/views/livewire/qualification/statistics-v2.blade.php` (lignes 357-377)

```blade
<div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm p-6"
    x-data="{ showPercentage: window.cityChartPercentageMode || false }"
    x-init="$watch('showPercentage', value => { if (typeof updateCityChart === 'function') updateCityChart(value); })">

    <div class="flex items-center justify-between mb-4">
        <h3 class="text-lg font-semibold text-gray-900 dark:text-white">
            Qualifications complétées par utilisateur
        </h3>

        <!-- Bouton Toggle -->
        <button @click="showPercentage = !showPercentage"
            class="flex items-center gap-2 px-3 py-1.5 text-sm font-medium rounded-lg transition-colors"
            :class="showPercentage ?
                'bg-blue-100 dark:bg-blue-900 text-blue-700 dark:text-blue-300' :
                'bg-gray-100 dark:bg-gray-700 text-gray-700 dark:text-gray-300'">
            <svg class="w-4 h-4"><!-- Icône calculatrice --></svg>
            <span x-text="showPercentage ? 'Voir en valeurs' : 'Voir en %'"></span>
        </button>
    </div>

    <div wire:ignore class="h-80">
        <canvas id="cityComparisonChart"></canvas>
    </div>
</div>
```

#### Explication du code Alpine.js

**1. Initialisation de l'état**

```javascript
x-data="{ showPercentage: window.cityChartPercentageMode || false }"
```

- Crée une variable réactive `showPercentage`
- Initialise à partir de `window.cityChartPercentageMode` (état global)
- Par défaut : `false` (mode valeurs)

**2. Observateur de changement**

```javascript
x-init="$watch('showPercentage', value => {
    if (typeof updateCityChart === 'function')
        updateCityChart(value);
})"
```

- `x-init` : Exécuté une fois au montage du composant
- `$watch('showPercentage', ...)` : Observe les changements de `showPercentage`
- Appelle `updateCityChart(value)` quand l'état change
- Vérification de sécurité : `typeof updateCityChart === 'function'`

**3. Gestionnaire de clic**

```javascript
@click="showPercentage = !showPercentage"
```

- Inverse l'état booléen au clic
- Déclenche automatiquement le `$watch` qui appelle `updateCityChart()`

**4. Classes CSS conditionnelles**

```javascript
:class="showPercentage ?
    'bg-blue-100 dark:bg-blue-900 text-blue-700 dark:text-blue-300' :
    'bg-gray-100 dark:bg-gray-700 text-gray-700 dark:text-gray-300'"
```

- Bleu = mode pourcentage (actif)
- Gris = mode valeurs (défaut)
- Compatible dark mode

**5. Texte dynamique**

```javascript
x-text="showPercentage ? 'Voir en valeurs' : 'Voir en %'"
```

- Affiche le **prochain** mode disponible (pas le mode actuel)

### Code JavaScript - Gestion du graphique

**Localisation :** `statistics-v2.blade.php` (lignes 636-730)

#### 1. Variables globales

```javascript
// Initialiser l'état du toggle si ce n'est pas déjà fait
if (typeof window.cityChartPercentageMode === 'undefined') {
    window.cityChartPercentageMode = false;
}

// Stocker les données dans une variable globale pour le toggle
window.cityStatsData = {
    cityStats: statistics.cityStats,
    cityLabels: Object.values(statistics.cityStats).map(s => s.name),
    allUsers: new Set(),
    colors: colors,
    textColor: textColor,
    gridColor: gridColor
};
```

**Pourquoi des variables globales ?**

- `window.cityChartPercentageMode` : Préserve l'état entre les mises à jour de statistiques
- `window.cityStatsData` : Accessible par `updateCityChart()` à tout moment
- Permet au toggle de fonctionner même après un changement de période

#### 2. Fonction `updateCityChart(showPercentage)`

**Signature :**
```javascript
window.updateCityChart = function(showPercentage = false) {
    // ...
}
```

**Étape 1 : Sauvegarde de l'état**

```javascript
// Sauvegarder l'état du toggle
window.cityChartPercentageMode = showPercentage;
```

Permet de conserver le choix de l'utilisateur lors des mises à jour.

**Étape 2 : Destruction du graphique existant**

```javascript
// Détruire le graphique existant
if (chartInstances.cityComparisonChart) {
    chartInstances.cityComparisonChart.destroy();
}
```

Évite les fuites mémoire et les conflits.

**Étape 3 : Calcul des totaux par ville**

```javascript
// Calculer les totaux par ville
const cityTotals = Object.values(cityStats).map(city => city.total);
```

**Exemple :**
```javascript
cityStats = {
    castellane: { name: 'Castellane', total: 100, byUser: [...] },
    moustiers: { name: 'Moustiers', total: 50, byUser: [...] }
};

cityTotals = [100, 50];
```

**Étape 4 : Création des datasets avec calcul conditionnel**

```javascript
const cityDatasets = Array.from(allUsers).map((userName, index) => {
    return {
        label: userName,
        data: Object.values(cityStats).map((city, cityIndex) => {
            const userEntry = city.byUser.find(u => u.user_name === userName);
            const count = userEntry ? userEntry.count : 0;

            if (showPercentage) {
                // Calculer le pourcentage par rapport au total de la ville
                const total = cityTotals[cityIndex];
                return total > 0 ? Math.round((count / total) * 100 * 10) / 10 : 0;
            } else {
                return count;
            }
        }),
        backgroundColor: colors[index % colors.length]
    };
});
```

**Explication détaillée du calcul de pourcentage :**

```javascript
Math.round((count / total) * 100 * 10) / 10
```

- `(count / total) * 100` : Pourcentage brut (ex: 66.66666...)
- `* 10` : Multiplie par 10 (666.6666...)
- `Math.round(...)` : Arrondit (667)
- `/ 10` : Divise par 10 (66.7)

**Résultat :** Pourcentage avec 1 décimale

**Exemple concret :**

```javascript
// Ville A : 100 qualifications
// Agent 1 : 60 qualifications
// Agent 2 : 40 qualifications

// Mode Valeurs
data = [60, 40]

// Mode Pourcentage
data = [
    Math.round((60 / 100) * 100 * 10) / 10,  // 60.0%
    Math.round((40 / 100) * 100 * 10) / 10   // 40.0%
]
```

**Étape 5 : Configuration des tooltips**

```javascript
tooltip: {
    callbacks: {
        label: function(context) {
            const label = context.dataset.label || '';
            const value = context.parsed.y;

            if (showPercentage) {
                return `${label}: ${value}%`;
            } else {
                // Afficher aussi le pourcentage dans le tooltip en mode valeurs
                const cityIndex = context.dataIndex;
                const total = cityTotals[cityIndex];
                const percentage = total > 0 ? Math.round((value / total) * 100 * 10) / 10 : 0;
                return `${label}: ${value} (${percentage}%)`;
            }
        }
    }
}
```

**Bonus :** En mode valeurs, le tooltip affiche **aussi** le pourcentage !

**Exemples de tooltips :**
- Mode Valeurs : `Marie Dupont: 60 (60%)`
- Mode Pourcentage : `Marie Dupont: 60%`

**Étape 6 : Configuration de l'axe Y**

```javascript
y: {
    stacked: true,
    beginAtZero: true,
    max: showPercentage ? 100 : undefined,
    grid: { color: gridColor },
    ticks: {
        color: textColor,
        callback: function(value) {
            return showPercentage ? value + '%' : value;
        }
    }
}
```

**Différences selon le mode :**

| Propriété | Mode Valeurs | Mode Pourcentage |
|-----------|--------------|------------------|
| `max` | `undefined` (dynamique) | `100` (fixe) |
| Label | `60` | `60%` |
| Échelle | Adaptative | 0-100% |

**Étape 7 : Création du graphique Chart.js**

```javascript
chartInstances.cityComparisonChart = new Chart(cityCtx, {
    type: 'bar',
    data: { labels: cityLabels, datasets: cityDatasets },
    options: { /* ... */ }
});
```

**Étape 8 : Initialisation**

```javascript
// Initialiser le graphique avec l'état préservé (ou en mode valeurs par défaut)
window.updateCityChart(window.cityChartPercentageMode);
```

Utilise `window.cityChartPercentageMode` pour préserver l'état entre les mises à jour.

### Flux de données - Toggle en action

#### Scénario 1 : Premier chargement

```
1. Page chargée
   ↓
2. window.cityChartPercentageMode = undefined
   ↓
3. Initialisation à false
   ↓
4. Alpine.js : showPercentage = false
   ↓
5. updateCityChart(false) appelé
   ↓
6. Graphique créé en mode valeurs
   ↓
7. Bouton affiche "Voir en %"
```

#### Scénario 2 : Activation du mode pourcentage

```
1. Utilisateur clique sur "Voir en %"
   ↓
2. Alpine.js : showPercentage = true
   ↓
3. $watch détecte le changement
   ↓
4. updateCityChart(true) appelé
   ↓
5. window.cityChartPercentageMode = true (sauvegarde)
   ↓
6. Ancien graphique détruit
   ↓
7. Calcul des pourcentages : (count / total) * 100
   ↓
8. Nouveau graphique créé avec :
   - Données en %
   - max: 100
   - Labels avec "%"
   ↓
9. Bouton affiche "Voir en valeurs" (bleu)
```

#### Scénario 3 : Changement de période (avec toggle actif)

```
1. Utilisateur sélectionne "7 derniers jours"
   ↓
2. Livewire applyFilter() appelé
   ↓
3. dispatch('statistics-updated', statistics: [...])
   ↓
4. JavaScript reçoit l'événement
   ↓
5. initCharts(newStatistics) appelé
   ↓
6. window.cityStatsData mis à jour avec nouvelles données
   ↓
7. window.cityChartPercentageMode = true (préservé !)
   ↓
8. updateCityChart(window.cityChartPercentageMode)
   ↓
9. updateCityChart(true) → graphique en mode %
   ↓
10. Alpine.js : showPercentage = true (synchronisé)
    ↓
11. Bouton reste en état "Voir en valeurs" (bleu)
```

**✨ L'état du toggle est préservé !**

### Avantages de l'implémentation

#### 1. Performance
- Destruction propre du graphique (pas de fuite mémoire)
- Pas de rechargement de page
- Transition instantanée

#### 2. UX
- Feedback visuel immédiat (couleur du bouton)
- Texte clair ("Voir en %" vs "Voir en valeurs")
- Tooltips enrichis dans les deux modes
- État préservé entre les changements de filtre

#### 3. Maintenabilité
- Code modulaire (`updateCityChart` réutilisable)
- Variables globales clairement nommées
- Logique séparée (Alpine.js pour UI, JavaScript pour calculs)

#### 4. Extensibilité
- Facile d'ajouter d'autres modes (ex: logarithmique)
- Peut être répliqué sur d'autres graphiques
- Configuration centralisée dans `cityStatsData`

### Cas d'usage

**Exemple réel :**

```
Données brutes :
- Castellane : 150 qualifications (Marie: 90, Jean: 60)
- Moustiers : 30 qualifications (Marie: 25, Jean: 5)
```

**Mode Valeurs :**
```
Castellane : [90, 60] → barre de 150
Moustiers  : [25, 5]  → barre de 30
```
→ Castellane semble 5× plus importante
→ Difficile de comparer la répartition interne

**Mode Pourcentage :**
```
Castellane : [60%, 40%] → barre de 100%
Moustiers  : [83.3%, 16.7%] → barre de 100%
```
→ On voit que Marie est plus dominante à Moustiers (83%) qu'à Castellane (60%)
→ Comparaison équitable de la distribution

**Insight découvert :** Marie gère 83% des qualifications à Moustiers contre seulement 60% à Castellane. Jean a besoin de renfort à Moustiers !

---

## Optimisations et bonnes pratiques

### 1. Performances base de données

**Éviter N+1 :**
```php
Qualification::with('user')  // Eager loading
```

**Index recommandés :**
```sql
CREATE INDEX idx_qualifications_created_at ON qualifications(created_at);
CREATE INDEX idx_qualifications_city ON qualifications(city);
CREATE INDEX idx_qualifications_completed ON qualifications(completed);
```

### 2. Chunking pour les exports

```php
public function chunkSize(): int
{
    return 500;
}
```

Traite 500 enregistrements à la fois → économise la mémoire.

### 3. Gestion de la mémoire JavaScript

Destruction des graphiques avant recréation :
```javascript
chartInstances[key].destroy();
```

### 4. Compatibilité multi-bases

Détection automatique SQLite vs MySQL pour les fonctions de date.

### 5. Dark mode

Adaptation automatique des couleurs des graphiques selon le thème.

---

## Points d'extension possibles

### 1. Nouveaux KPIs

Dans `QualificationStatisticsService::getKPIs()`, ajouter :
- Temps moyen de complétion
- Taux d'abandon par étape
- Satisfaction visiteur

### 2. Nouveaux filtres

Dans `QualificationStatisticsV2` :
- Filtre par ville
- Filtre par agent
- Filtre par statut (complétées/en cours)

### 3. Nouveaux graphiques

Dans `statistics-v2.blade.php` :
- Carte géographique interactive
- Graphique de conversion (funnel)
- Heatmap des demandes

### 4. Export PDF

Créer une classe `QualificationsPdfExport` similaire à `QualificationsExport`.

### 5. Scheduled reports

Planifier des exports automatiques avec Laravel Task Scheduling :
```php
$schedule->call(function () {
    // Générer et envoyer rapport hebdomadaire
})->weekly();
```

---

## Dépendances

### Backend (Composer)
- `laravel/framework` : Framework principal
- `livewire/livewire` : Composants réactifs
- `maatwebsite/excel` : Export Excel
- `nesbot/carbon` : Manipulation de dates

### Frontend (CDN)
- `chart.js@4.4.0` : Graphiques
- `chartjs-adapter-date-fns` : Gestion des dates dans Chart.js
- `flatpickr@4.6.13` : Sélecteurs de dates
- `alpine.js` : Réactivité légère (via Livewire)

---

## Conclusion

Ce système de statistiques est une solution complète et performante qui :

✅ Offre une visualisation riche et interactive des données
✅ Permet des exports Excel détaillés et personnalisables
✅ Utilise une architecture modulaire et maintenable
✅ Optimise les performances avec chunking et eager loading
✅ S'adapte au mode sombre et à différentes bases de données
✅ Peut être étendu facilement avec de nouvelles fonctionnalités

**Architecture claire :** Séparation entre présentation (Vue), logique métier (Service), et coordination (Composant Livewire).

**Performance :** Requêtes optimisées, traitement par lots, gestion mémoire JavaScript.

**UX moderne :** Mises à jour sans rechargement, graphiques interactifs, modal fluide.
