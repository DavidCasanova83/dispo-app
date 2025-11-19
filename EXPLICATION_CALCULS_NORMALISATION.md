# Explication des calculs de normalisation - Statistiques ✅ CORRIGÉ

## Vue d'ensemble

Ce document explique **pas à pas** comment les statistiques sont calculées dans les deux modes d'affichage :
- **Mode ABSOLU** : Nombres bruts (ex: 312 demandes)
- **Mode NORMALISÉ** : Pourcentages de visiteurs (ex: 38.8% des visiteurs)

Nous utilisons comme exemple les **"Demandes générales"** avec les 804 qualifications de démonstration réelles.

---

## 🔴 LE PROBLÈME QUI ÉTAIT PRÉSENT AVANT LA CORRECTION

### ❌ Ancienne méthode (INCORRECTE)

L'ancienne normalisation divisait par le **total de toutes les demandes** :

```
Exemple incorrect:
- 450 demandes "Randonnée"
- 2050 demandes au total (tous types confondus)
→ Pourcentage = 450 / 2050 = 22% du total des demandes

PROBLÈME : Ce calcul ne dit RIEN sur les visiteurs !
```

**Pourquoi c'était faux ?**
- Comparait toujours les volumes absolus
- Une ville avec 2000 visiteurs dominait visuellement une ville avec 500 visiteurs
- Ne permettait PAS de comparaison équitable entre villes

### ✅ Nouvelle méthode (CORRECTE)

La nouvelle normalisation divise par le **nombre de visiteurs** :

```
Exemple correct:
- 450 demandes "Randonnée"
- 800 visiteurs au total
→ Pourcentage = 450 / 800 = 56.25% des visiteurs

CORRECT : 56.25% des visiteurs veulent faire de la randonnée !
```

**Pourquoi c'est correct ?**
- Montre quel % de visiteurs demande chaque activité
- Permet de comparer équitablement des villes avec volumes différents
- Révèle les vrais intérêts locaux, indépendamment du volume

---

## 📊 Les données réelles (après seeder)

### Répartition des 804 qualifications par ville

```
La Palud-sur-Verdon : 300 qualifications (37.3% du total)
Entrevaux           : 200 qualifications (24.9% du total)
Saint-André         : 150 qualifications (18.7% du total)
Colmars-les-Alpes   : 101 qualifications (12.6% du total)
Annot               : 53 qualifications  (6.6% du total)
━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
TOTAL               : 804 qualifications (100%)
```

### Comment sont générées les demandes ?

Dans chaque qualification créée par le seeder, **2 à 4 demandes générales** sont sélectionnées aléatoirement parmi :
- Randonnée
- VTT
- Escalade
- Sports nautiques
- Patrimoine culturel
- Gastronomie
- Hébergement
- Transport

**Important :** Une même qualification peut avoir plusieurs demandes (multi-select). C'est pourquoi il faut diviser par le nombre de visiteurs, pas par le total de demandes !

---

## 🧮 Exemple concret : Calcul pour "VTT"

### Résultats réels après le seeder :

```
Total de demandes "VTT" : 312
Total de visiteurs : 804
```

---

## 1️⃣ MODE ABSOLU (valeurs brutes)

### Calcul

En mode absolu, on affiche simplement le nombre brut de demandes.

```php
// Code simplifié
$demands = $qualifications->flatMap(function($q) {
    return $q->form_data['generalRequests'] ?? [];
})->countBy();

// Résultat brut
$vttCount = 312; // nombre de fois où "VTT" a été sélectionné
```

### Affichage

```
VTT : 312 demandes
```

### Graphique (mode absolu)

```
VTT          ████████████████████████████████ 312
Escalade     ███████████████████████████████  306
Hébergement  ███████████████████████████████  306
Gastronomie  █████████████████████████████    299
Patrimoine   ████████████████████████████     292
```

**📊 Ce qu'on voit :** Le nombre brut de demandes.

---

## 2️⃣ MODE NORMALISÉ (pourcentage de visiteurs) ✅ CORRECT

### Calcul détaillé

En mode normalisé, on divise par le **nombre total de visiteurs** (pas de demandes !) :

```
Pourcentage = (Nombre de demandes / Nombre de visiteurs) × 100
```

**Exemple avec "VTT" :**

```
Calcul : (312 / 804) × 100 = 38.8%
```

**Interprétation :** 38.8% des 804 visiteurs ont demandé "VTT".

### Code de la méthode convertToVisitorPercentage()

```php
protected function convertToVisitorPercentage(
    array $data,
    int $totalVisitors,
    string $displayMode
): array {
    if ($displayMode !== 'normalized' || empty($data)) {
        return $data; // Mode absolu
    }

    if ($totalVisitors === 0) {
        return $data; // Éviter division par zéro
    }

    $result = [];
    foreach ($data as $key => $value) {
        // Diviser par le nombre de VISITEURS (pas de demandes!)
        $result[$key] = round(($value / $totalVisitors) * 100, 1);
    }

    return $result;
}
```

### Affichage

```
VTT : 38.8% des visiteurs
```

### Graphique (mode normalisé)

```
VTT          ████████████████████████████████████████ 38.8%
Escalade     ████████████████████████████████████████ 38.1%
Hébergement  ████████████████████████████████████████ 38.1%
Gastronomie  ███████████████████████████████████████  37.2%
Patrimoine   ██████████████████████████████████████   36.3%
```

**📊 Ce qu'on voit :** Le pourcentage de visiteurs intéressés par chaque activité.

---

## 🏙️ COMPARAISON PAR VILLE (l'avantage principal!)

### Scénario : La Palud (300 visiteurs) vs Annot (53 visiteurs)

#### Résultats réels pour "Cartes touristiques"

```
La Palud : 129 demandes sur 300 visiteurs
Annot    : 23 demandes sur 53 visiteurs
```

### ❌ Mode absolu (biaisé)

```
La Palud : 129 demandes ████████████████████████████████████████
Annot    : 23 demandes  ███████
```

**Problème :** La Palud semble dominer visuellement (129 >> 23), mais est-ce vraiment le cas ?

### ✅ Mode normalisé (équitable)

**Calculs :**
```
La Palud : (129 / 300) × 100 = 43.0%
Annot    : (23 / 53) × 100 = 43.4%
```

**Affichage :**
```
La Palud : 43.0% des visiteurs ████████████████████████████████████
Annot    : 43.4% des visiteurs ████████████████████████████████████
```

**🎯 INSIGHT :** L'intérêt pour les cartes touristiques est **identique** dans les deux villes ! La normalisation révèle la vraie popularité locale.

---

## 📐 Formule mathématique complète

### Mode Absolu
```
Valeur affichée = Nombre de demandes brut
```

### Mode Normalisé (CORRECT)
```
Valeur affichée = (Nombre de demandes / Nombre de visiteurs) × 100

Où :
- Nombre de demandes = Combien de fois cette option a été sélectionnée
- Nombre de visiteurs = Nombre total de qualifications (visiteurs)
- Résultat = Pourcentage de visiteurs qui ont sélectionné cette option
```

### ⚠️ À NE PAS CONFONDRE AVEC (l'ancienne méthode incorrecte)

```
❌ INCORRECT : (Nombre de demandes / Total de toutes les demandes) × 100
   → Cela compare les volumes de demandes, pas les visiteurs !
```

---

## 🔄 Flux de données Backend → Frontend

### 1. Backend (QualificationStatisticsService.php)

```php
public function getDemandStats(..., $displayMode = 'normalized')
{
    // Étape 1 : Récupérer toutes les qualifications
    $qualifications = $this->baseQuery(...)->get();

    // Étape 2 : Compter le NOMBRE DE VISITEURS (pas de demandes!)
    $totalQualifications = $qualifications->count(); // 804

    // Étape 3 : Compter les demandes (avec multi-select)
    $generalRequestsRaw = $qualifications->flatMap(function($q) {
        return $q->form_data['generalRequests'] ?? [];
    })->countBy()->sortDesc()->take(10);
    // Résultat : ['VTT' => 312, 'Escalade' => 306, ...]

    // Étape 4 : Convertir en pourcentage de VISITEURS
    $generalRequests = $this->convertToVisitorPercentage(
        $generalRequestsRaw->toArray(),
        $totalQualifications, // 804 visiteurs
        $displayMode
    );
    // Résultat si normalized : ['VTT' => 38.8, 'Escalade' => 38.1, ...]

    return ['generalRequests' => $generalRequests, ...];
}
```

### 2. Livewire (QualificationStatisticsV2.php)

```php
public function getStatisticsData()
{
    $service = new QualificationStatisticsService();

    return [
        'displayMode' => $this->globalDisplayMode, // 'normalized' ou 'absolute'
        'demands' => $service->getDemandStats(..., $this->globalDisplayMode),
        ...
    ];
}
```

### 3. Frontend (JavaScript/Alpine.js)

```javascript
// Les données arrivent déjà calculées selon le mode
if (displayMode === 'normalized') {
    label = `${value}% des visiteurs`; // "38.8% des visiteurs"
} else {
    label = `${value} demandes`;        // "312 demandes"
}
```

---

## 🎯 Cas d'usage

### Quand utiliser le mode ABSOLU ?

✅ Pour voir les **volumes réels** de demandes
✅ Pour la planification des ressources (ex: combien de guides embaucher)
✅ Quand on analyse UNE SEULE ville

**Exemple :** "Nous avons reçu 312 demandes de VTT à La Palud, il faut prévoir du matériel."

### Quand utiliser le mode NORMALISÉ ?

✅ Pour **comparer plusieurs villes** avec volumes différents
✅ Pour identifier les **intérêts relatifs** (popularité)
✅ Pour des **analyses marketing** (profil type des visiteurs)

**Exemple :** "43% des visiteurs d'Annot veulent des cartes vs 43% à La Palud : même intérêt !"

---

## 🧪 Fiabilité statistique

Le système indique automatiquement la fiabilité selon la taille d'échantillon :

```php
if ($count >= 100) {
    $reliability = 'high';      // ≥100 visiteurs : Fiabilité ÉLEVÉE
} elseif ($count >= 30) {
    $reliability = 'medium';    // 30-99 : Fiabilité MOYENNE
} else {
    $reliability = 'low';       // <30 : Fiabilité FAIBLE
}
```

**Marge d'erreur (IC 95%) :**
```php
$marginError = round(1.96 * sqrt(0.25 / $count) * 100, 1);
```

**Exemple :**
- Annot (53 visiteurs) : ±13.5% de marge d'erreur
- La Palud (300 visiteurs) : ±5.7% de marge d'erreur

---

## 📝 Checklist de compréhension

Pour vérifier que vous avez bien compris, posez-vous ces questions :

- [ ] **Q1 :** En mode normalisé, par quoi divise-t-on ?
  **R :** Par le nombre de visiteurs (qualifications)

- [ ] **Q2 :** Pourquoi ne divise-t-on PAS par le total de demandes ?
  **R :** Car on perdrait la possibilité de comparer équitablement les villes

- [ ] **Q3 :** Si une ville a 100 visiteurs et 45 demandes "Randonnée", quel est le pourcentage normalisé ?
  **R :** (45 / 100) × 100 = 45% des visiteurs

- [ ] **Q4 :** En mode normalisé, peut-on avoir des pourcentages >100% ?
  **R :** Non, car on divise par le nombre de visiteurs (max 100%)

- [ ] **Q5 :** Quelle méthode utiliser pour comparer des villes de tailles différentes ?
  **R :** Mode normalisé

---

## 🔑 Points clés à retenir

1. **Mode ABSOLU** = Valeurs brutes (ex: 312 demandes)
2. **Mode NORMALISÉ** = % de visiteurs (ex: 38.8% des visiteurs)
3. **Formule correcte** : `(Demandes / Visiteurs) × 100`
4. **Multi-select** : Un visiteur peut faire plusieurs demandes → important de diviser par visiteurs
5. **Comparaison équitable** : Le mode normalisé annule le biais de volume
6. **Interprétation** : "X% des visiteurs demandent cette activité"
7. **Différence cruciale** : `convertToVisitorPercentage()` divise par VISITEURS, pas par total de demandes

---

## 📚 Ressources connexes

- **Seeder de démo** : `database/seeders/QualificationDemoSeeder.php`
- **Service de statistiques** : `app/Services/QualificationStatisticsService.php`
  - Méthode `getDemandStats()` : Statistiques des demandes
  - Méthode `convertToVisitorPercentage()` : Conversion en % de visiteurs ✅
  - Méthode `convertToDisplayMode()` : Conversion classique (pour profils/pays)
- **Composant Livewire** : `app/Livewire/QualificationStatisticsV2.php`
- **Vue** : `resources/views/livewire/qualification/statistics-v2.blade.php`

---

**✅ Document mis à jour avec la méthode de calcul correcte !**
