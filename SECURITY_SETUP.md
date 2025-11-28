# Configuration de Sécurité du Formulaire Public

Ce document décrit toutes les mesures de sécurité implémentées pour protéger le formulaire public de commande d'images contre les abus, le spam et les attaques.

## 📋 Résumé des Protections Implémentées

### ✅ Phase 1 - Protection Critique (COMPLÉTÉ)

1. **Rate Limiting (Limitation de débit)**
2. **Honeypot (Piège à bots)**
3. **CAPTCHA Cloudflare Turnstile**

### ✅ Phase 2 - Validation Avancée (COMPLÉTÉ)

4. **Validation Email Avancée**
5. **Sanitisation des Entrées**
6. **Filtrage de Contenu Spam**

### ✅ Phase 3 - Tracking & Sécurité (COMPLÉTÉ)

7. **Suivi IP et User Agent**
8. **Correction Race Condition Stock**
9. **Validation Renforcée**

---

## 🛡️ Détails des Protections

### 1. Rate Limiting (Limitation de débit)

**Fichier**: `app/Providers/AppServiceProvider.php`

**Protection**:
- **10 consultations par minute** par IP
- **5 soumissions par heure** par IP

**Fonctionnement**:
```php
// Limite les visites de la page
Limit::perMinute(10)->by($request->ip())

// Limite les soumissions du formulaire
Limit::perHour(5)->by($request->ip())->when($request->isMethod('POST'))
```

**Avantages**:
- Empêche les attaques par force brute
- Bloque les bots automatisés
- Protège contre l'épuisement des ressources

---

### 2. Honeypot (Piège à bots)

**Package**: `spatie/laravel-honeypot`
**Fichiers**:
- `app/Livewire/PublicImageOrderForm.php`
- `resources/views/livewire/public-image-order-form.blade.php`

**Protection**:
- Champs cachés invisibles pour les humains
- Validation du timestamp (empêche la soumission instantanée)

**Fonctionnement**:
```blade
<x-honeypot wire:model="honeypot" />
```

Le composant ajoute automatiquement des champs cachés que seuls les bots remplissent. Si ces champs sont remplis, la soumission est rejetée avec un HTTP 403.

**Avantages**:
- Protection silencieuse (invisible pour l'utilisateur)
- Capture les bots simples
- Pas d'impact sur l'UX

---

### 3. CAPTCHA Cloudflare Turnstile

**Package**: `coderflex/laravel-turnstile`
**Fichiers**:
- `.env` (configuration)
- `resources/views/livewire/public-image-order-form.blade.php`

**⚠️ CONFIGURATION REQUISE**:

1. Créez un compte sur https://dash.cloudflare.com/
2. Accédez à "Turnstile" dans le dashboard
3. Créez un nouveau site
4. Copiez les clés dans votre `.env`:

```env
TURNSTILE_SITE_KEY=votre_site_key_ici
TURNSTILE_SECRET_KEY=votre_secret_key_ici
```

**Protection**:
- Validation côté client et serveur
- Détection intelligente des bots
- Invisible pour les utilisateurs légitimes
- Score de confiance pour chaque soumission

**Avantages**:
- Plus moderne que reCAPTCHA
- Respecte la vie privée (RGPD)
- Gratuit jusqu'à 1M requêtes/mois
- Meilleure UX (pas de puzzle)

---

### 4. Validation Email Avancée

**Fichier**: `app/Rules/NotDisposableEmail.php`

**Protections**:
1. **Emails jetables bloqués** (via `mailchecker`)
   - Bloque 10,000+ domaines d'emails temporaires
   - Liste mise à jour régulièrement

2. **Validation DNS MX**
   - Vérifie que le domaine a un serveur de messagerie
   - Détecte les domaines inexistants

3. **Format RFC strict**
   ```php
   'email' => ['required', 'email:rfc,dns', 'max:255', new NotDisposableEmail()]
   ```

**Exemple de domaines bloqués**:
- `tempmail.com`
- `guerrillamail.com`
- `10minutemail.com`
- etc.

**Avantages**:
- Empêche les inscriptions frauduleuses
- Garantit des emails valides et livrables
- Réduit les bounces

---

### 5. Sanitisation des Entrées

**Package**: `stevebauman/purify`
**Fichier**: `app/Livewire/PublicImageOrderForm.php`

**Protection**:
Tous les champs texte sont nettoyés avant sauvegarde:

```php
$sanitizedData = [
    'last_name' => Purify::clean($this->last_name),
    'first_name' => Purify::clean($this->first_name),
    'company' => Purify::clean($this->company),
    'address_line1' => Purify::clean($this->address_line1),
    'address_line2' => Purify::clean($this->address_line2),
    'city' => Purify::clean($this->city),
    'country' => Purify::clean($this->country),
    'customer_notes' => Purify::clean($this->customer_notes),
];
```

**Protection contre**:
- Injections XSS (Cross-Site Scripting)
- HTML malveillant
- Scripts JavaScript
- Tags dangereux

**Avantages**:
- Protection en profondeur
- Pas d'impact sur les données légitimes
- Compatible avec UTF-8 et caractères accentués

---

### 6. Filtrage de Contenu Spam

**Fichier**: `app/Rules/NoSpamContent.php`

**Détections**:

1. **URLs interdites**
   ```regex
   /(https?:\/\/|www\.)/i
   ```

2. **Mots-clés spam** (40+ mots):
   - viagra, casino, lottery
   - click here, free money
   - bitcoin, get rich quick
   - etc.

3. **Répétitions excessives**
   ```regex
   /(.)\1{10,}/  // Détecte "aaaaaaaaaaaaa"
   ```

4. **Trop de majuscules** (> 50%)
   - Détecte les messages en CAPS LOCK (spam courant)

**Appliqué sur**:
- `customer_notes`
- `company` (pour les professionnels)

**Avantages**:
- Bloque les messages spam automatiques
- Messages d'erreur clairs pour l'utilisateur
- Configurable facilement

---

### 7. Suivi IP et User Agent

**Migration**: `2025_11_22_145556_add_security_tracking_to_image_orders_table.php`

**Nouveaux champs**:
```php
$table->string('ip_address', 45)->nullable();  // IPv4 + IPv6
$table->text('user_agent')->nullable();
$table->index('ip_address');  // Index pour recherches rapides
```

**Collecte automatique**:
```php
'ip_address' => request()->ip(),
'user_agent' => request()->userAgent(),
```

**Utilisations**:
- Détection d'abus (multiple commandes même IP)
- Géolocalisation possible
- Analyse des patterns de fraude
- Blacklisting si nécessaire

**⚠️ Conformité RGPD**:
- Informez les utilisateurs dans vos CGU
- Justification: sécurité et prévention de la fraude
- Durée de conservation limitée recommandée

---

### 8. Correction Race Condition Stock

**Fichier**: `app/Livewire/PublicImageOrderForm.php`

**Problème**:
Deux utilisateurs commandent simultanément la dernière image → les deux commandes passent

**Solution**:
```php
// AVANT (vulnérable)
$image = Image::find($imageId);
if ($image->quantity_available >= $quantity) {
    $image->decrement('quantity_available', $quantity);
}

// APRÈS (sécurisé)
$image = Image::where('id', $imageId)->lockForUpdate()->first();
if ($image->quantity_available >= $quantity) {
    $image->decrement('quantity_available', $quantity);
} else {
    DB::rollBack();  // Annule la transaction si stock insuffisant
}
```

**Protection**:
- Verrou exclusif en base de données
- Transaction atomique
- Impossibilité de vendre en surstock

**Avantages**:
- Intégrité des données garantie
- Pas de ventes en négatif
- Concurrent-safe

---

### 9. Validation Renforcée

**Nouveaux patterns regex**:

```php
// Noms/Prénoms - seulement lettres, espaces, tirets, apostrophes
'regex:/^[a-zA-ZÀ-ÿ\s\-\']+$/'

// Code postal - alphanumériques, espaces, tirets
'regex:/^[0-9A-Za-z\s\-]+$/'

// Téléphone pays - + et chiffres
'regex:/^\+?[0-9]+$/'

// Numéro téléphone - chiffres, espaces, tirets, parenthèses
'regex:/^[0-9\s\-\(\)]+$/'
```

**Validations strictes**:
- Formats prédéfinis pour chaque champ
- Rejet des caractères suspects
- Messages d'erreur explicites

---

## 🚀 Mise en Production

### Checklist de Configuration

- [x] Packages installés (`composer install`)
- [x] Migration exécutée (`php artisan migrate`)
- [ ] **Clés Turnstile configurées** dans `.env`
- [ ] Cache vidé (`php artisan optimize:clear`)
- [ ] Tests de soumission effectués
- [ ] Monitoring activé

### Configuration Turnstile (OBLIGATOIRE)

1. **Obtenir les clés**:
   - https://dash.cloudflare.com/ → Turnstile
   - Créer un nouveau site
   - Domain: votre-domaine.com
   - Mode: Managed (recommandé)

2. **Configurer `.env`**:
   ```env
   TURNSTILE_SITE_KEY=1x00000000000000000000AA
   TURNSTILE_SECRET_KEY=1x0000000000000000000000000000000AA
   ```

3. **Tester**:
   - Visitez `/commander-images`
   - Vérifiez que le widget Turnstile s'affiche
   - Soumettez le formulaire
   - Vérifiez dans les logs Cloudflare

---

## 📊 Monitoring & Analyse

### Métriques à Surveiller

1. **Taux de rejet Honeypot**:
   ```sql
   -- Chercher dans les logs Laravel
   -- "Spam detected" via honeypot
   ```

2. **Échecs CAPTCHA**:
   - Dashboard Cloudflare Turnstile
   - Score de confiance moyen
   - Taux de réussite vs échec

3. **Rate Limiting**:
   ```sql
   -- Erreurs 429 dans les logs
   -- IPs bloquées fréquemment
   ```

4. **Commandes par IP**:
   ```sql
   SELECT
       ip_address,
       COUNT(*) as total_orders,
       DATE(created_at) as order_date
   FROM image_orders
   WHERE created_at >= NOW() - INTERVAL 7 DAY
   GROUP BY ip_address, DATE(created_at)
   HAVING total_orders > 5
   ORDER BY total_orders DESC;
   ```

5. **Emails jetables détectés**:
   ```php
   // Ajouter logging dans NotDisposableEmail
   Log::warning('Disposable email blocked', ['email' => $value]);
   ```

---

## 🔧 Maintenance

### Mises à Jour Régulières

```bash
# Mettre à jour la liste des emails jetables
composer update fgribreau/mailchecker

# Mettre à jour les packages de sécurité
composer update spatie/laravel-honeypot coderflex/laravel-turnstile stevebauman/purify
```

### Ajuster les Limites

Si trop restrictif:
```php
// app/Providers/AppServiceProvider.php
Limit::perMinute(20)->by($request->ip())  // Au lieu de 10
Limit::perHour(10)->by($request->ip())    // Au lieu de 5
```

### Ajouter des Mots-clés Spam

```php
// app/Rules/NoSpamContent.php
$spamKeywords = [
    'viagra', 'casino', 'lottery',
    // Ajoutez vos mots-clés ici
    'nouveau_mot_spam',
];
```

---

## 🐛 Dépannage

### CAPTCHA ne s'affiche pas

1. Vérifiez `.env`:
   ```bash
   php artisan config:clear
   php artisan config:cache
   ```

2. Vérifiez la console navigateur:
   - F12 → Console
   - Erreurs de chargement script Turnstile?

3. Vérifiez le domaine configuré dans Cloudflare

### Honeypot bloque les vrais utilisateurs

- Augmentez le délai minimum:
  ```php
  // config/honeypot.php
  'amount_of_seconds' => 2,  // Par défaut 4
  ```

### Rate Limit trop strict

- Augmentez les limites dans `AppServiceProvider.php`
- Ou exemptez certaines IPs:
  ```php
  if ($request->ip() === 'IP_DE_CONFIANCE') {
      return Limit::none();
  }
  ```

### Emails légitimes bloqués

- Vérifiez les logs
- Ajoutez exception dans `NotDisposableEmail.php`:
  ```php
  if ($domain === 'domaine-legitime.com') {
      return;  // Autorisé
  }
  ```

---

## 📈 Améliorations Futures (Optionnel)

### Phase 4 - Avancé

- **Akismet** pour détection spam ML
- **IP Geolocation** pour bloquer certains pays
- **Admin Dashboard** pour gérer blacklist/whitelist
- **Webhook Cloudflare** pour logs en temps réel
- **2FA Email** pour confirmation commande
- **Limitation par email** (ex: 3 commandes max/jour)

---

## 📚 Documentation des Packages

- **Honeypot**: https://github.com/spatie/laravel-honeypot
- **Turnstile**: https://github.com/coderflex/laravel-turnstile
- **Purify**: https://github.com/stevebauman/purify
- **MailChecker**: https://github.com/FGRibreau/mailchecker
- **Laravel Rate Limiting**: https://laravel.com/docs/11.x/rate-limiting

---

## ✅ Résumé

**Protections Actives**:
- ✅ Rate Limiting (10/min, 5/heure)
- ✅ Honeypot (anti-bot silencieux)
- ✅ CAPTCHA Turnstile (requiert config)
- ✅ Email validation (DNS + jetables)
- ✅ Sanitisation XSS
- ✅ Filtrage spam contenu
- ✅ IP tracking
- ✅ Race condition fixée
- ✅ Validation regex stricte

**Niveau de Sécurité**: ⭐⭐⭐⭐⭐ (Excellent)

**Action Requise**: Configurez les clés Turnstile dans `.env`

---

**Dernière mise à jour**: 22 novembre 2025
**Version**: 1.0.0
