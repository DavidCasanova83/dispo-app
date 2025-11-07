# Système d'envoi d'emails Mailjet - Documentation complète

## 🎯 Objectif

Implémenter un système d'envoi d'emails interactifs permettant :

1. D'envoyer un email à chaque hébergeur avec deux boutons de réponse (✅ Disponibilités / ❌ Pas de disponibilités)
2. De mettre à jour automatiquement le statut en base de données après le clic
3. De remplacer les statuts Active/Inactive/Pending par Disponible/Indisponible/En Attente

## ✅ Implémentation réalisée

### 📦 1. Installation et configuration Mailjet

**Package installé :**

-   `mailjet/mailjet-apiv3-php` v1.6.5

**Fichiers de configuration modifiés :**

-   `config/services.php` - Ajout des clés API Mailjet
-   `config/mail.php` - Configuration du mailer Mailjet

### 🗄️ 2. Base de données

**Migrations créées :**

**Migration 1 :** `2025_11_07_133310_add_email_tracking_fields_to_accommodations_table.php`

-   Ajout de `email_sent_at` (datetime) - Date d'envoi de l'email
-   Ajout de `email_response_token` (string, unique) - Token de sécurité pour les callbacks
-   Ajout de `last_response_at` (datetime) - Date de la dernière réponse

**Migration 2 :** `2025_11_07_133347_update_accommodation_status_values.php`

-   Mise à jour des valeurs de statut :
    -   `pending` → `en_attente`
    -   `active` → `disponible`
    -   `inactive` → `indisponible`

### 🏗️ 3. Architecture du code

**Services créés :**

-   `app/Services/MailjetService.php` - Service pour interagir avec l'API Mailjet

**Jobs créés :**

-   `app/Jobs/SendAccommodationAvailabilityEmail.php` - Job en queue pour l'envoi asynchrone des emails

**Controllers créés :**

-   `app/Http/Controllers/AccommodationResponseController.php` - Gestion des réponses (callbacks)

**Vues créées :**

-   `resources/views/emails/availability-request.blade.php` - Template HTML de l'email avec boutons
-   `resources/views/accommodation-response.blade.php` - Page de confirmation après clic

**Modèles modifiés :**

-   `app/Models/Accommodation.php` - Ajout des méthodes :
    -   `generateResponseToken()` - Génère un token unique pour le tracking
    -   `markEmailSent()` - Marque l'email comme envoyé
    -   `updateAvailability($available)` - Met à jour le statut de disponibilité

**Composants Livewire modifiés :**

-   `app/Livewire/AccommodationsList.php` - Ajout de la méthode `sendAvailabilityEmails()`
-   `resources/views/livewire/accommodations-list.blade.php` - Ajout du bouton "📧 Envoyer les mails"

**Routes ajoutées :**

-   `GET /accommodation/response` - Route publique pour traiter les clics sur les boutons

---

## ⚙️ Configuration requise

### 1. Variables d'environnement (.env)

Ajoutez ces lignes à votre fichier `.env` :

```env
# Clés API Mailjet (à obtenir sur https://app.mailjet.com/account/api_keys)
MAILJET_APIKEY=votre_cle_api_publique
MAILJET_APISECRET=votre_cle_api_secrete

# Email d'expédition
MAIL_FROM_ADDRESS=noreply@votredomaine.com
MAIL_FROM_NAME="Votre Application"

# URL de l'application (importante pour générer les liens de callback)
APP_URL=http://localhost:8000

# Configuration de la queue (pour l'envoi asynchrone)
QUEUE_CONNECTION=database
```

### 2. Configuration de la queue

**Pour le développement (synchrone) :**
Modifiez dans `.env` :

```env
QUEUE_CONNECTION=sync
```

**Pour la production (asynchrone - recommandé) :**
Gardez :

```env
QUEUE_CONNECTION=database
```

Et lancez un worker :

```bash
php artisan queue:work
```

### 3. Obtenir les clés API Mailjet

1. Créez un compte sur [Mailjet](https://www.mailjet.com/)
2. Allez dans **Account Settings** → **API Keys**
3. Copiez votre **API Key** (publique) et **Secret Key** (privée)
4. Collez-les dans votre fichier `.env`

---

## 🧪 Guide de test complet

### Prérequis

Assurez-vous que :

-   La base de données est configurée
-   Les migrations sont exécutées
-   Les clés Mailjet sont configurées dans `.env`

### Étape 1 : Préparation de la base de données

```bash
# Si ce n'est pas déjà fait, exécuter les migrations
php artisan migrate

# Vérifier que la table accommodations contient des données avec des emails
php artisan tinker
```

Dans tinker :

```php
# Compter les hébergements avec email
\App\Models\Accommodation::whereNotNull('email')->count();

# Afficher quelques exemples
\App\Models\Accommodation::whereNotNull('email')->take(3)->get(['id', 'name', 'email', 'status']);

# Sortir de tinker
exit
```

### Étape 2 : Lancer le serveur Laravel

**Option A - Mode développement simple (queue synchrone) :**

```bash
# Modifier .env pour utiliser la queue synchrone
# QUEUE_CONNECTION=sync

# Lancer le serveur
php artisan serve
```

**Option B - Mode production (queue asynchrone - recommandé pour tester le système complet) :**

Ouvrez **2 terminaux** :

**Terminal 1 - Serveur web :**

```bash
php artisan serve
```

**Terminal 2 - Queue worker :**

```bash
php artisan queue:work --tries=3 --timeout=90
```

### Étape 3 : Tester l'envoi d'emails

1. **Accéder à l'application :**

    ```
    http://localhost:8000
    ```

2. **Se connecter :**

    - Utilisez vos identifiants de connexion

3. **Accéder à la page Accommodations :**

    ```
    http://localhost:8000/accommodations
    ```

4. **Cliquer sur le bouton "📧 Envoyer les mails"**

    - Une confirmation vous sera demandée
    - Confirmez l'envoi

5. **Observer les résultats :**
    - Un message de succès doit s'afficher : "Envoi de X emails en cours..."
    - Si vous utilisez la queue asynchrone, vérifiez le terminal du queue worker pour voir les jobs s'exécuter

### Étape 4 : Vérifier les logs

```bash
# Voir les logs en temps réel
tail -f storage/logs/laravel.log

# Ou voir les 50 dernières lignes
tail -50 storage/logs/laravel.log
```

Vous devriez voir des entrées comme :

```
[2025-11-07 13:33:45] local.INFO: Availability email sent to accommodation 123
[2025-11-07 13:33:46] local.INFO: Availability email sent to accommodation 124
```

### Étape 5 : Vérifier l'envoi dans Mailjet

1. Connectez-vous à votre compte [Mailjet](https://app.mailjet.com/)
2. Allez dans **Statistics** → **Email Messages**
3. Vous devriez voir vos emails envoyés

### Étape 6 : Tester les callbacks (simulation de clic)

**Option A - Vérifier dans la base de données :**

```bash
php artisan tinker
```

```php
# Récupérer un hébergement qui a reçu un email
$accommodation = \App\Models\Accommodation::whereNotNull('email_response_token')->first();

# Afficher son token
echo "Token: " . $accommodation->email_response_token . "\n";
echo "Status actuel: " . $accommodation->status . "\n";

# Construire les URLs de test
echo "URL disponible: " . route('accommodation.response', ['token' => $accommodation->email_response_token, 'available' => 1]) . "\n";
echo "URL indisponible: " . route('accommodation.response', ['token' => $accommodation->email_response_token, 'available' => 0]) . "\n";

exit
```

**Option B - Tester les URLs dans le navigateur :**

1. Copiez une des URLs générées ci-dessus
2. Collez-la dans votre navigateur
3. Vous devriez voir la page de confirmation
4. Vérifiez en base que le statut a changé

**Option C - Tester avec curl :**

```bash
# Remplacez TOKEN par un vrai token de votre base
curl "http://localhost:8000/accommodation/response?token=TOKEN&available=1"

# Vérifier le changement en base
php artisan tinker
```

```php
$accommodation = \App\Models\Accommodation::where('email_response_token', 'VOTRE_TOKEN')->first();
echo "Nouveau statut: " . $accommodation->status . "\n";
echo "Dernière réponse: " . $accommodation->last_response_at . "\n";
exit
```

### Étape 7 : Tester avec un vrai email

Pour tester l'email complet avec un vrai destinataire :

```bash
php artisan tinker
```

```php
// Créer un hébergement de test avec votre email
$test = \App\Models\Accommodation::create([
    'apidae_id' => 'TEST_' . time(),
    'name' => 'Hébergement Test',
    'city' => 'Paris',
    'email' => 'votre.email@exemple.com', // METTEZ VOTRE VRAIE ADRESSE
    'status' => 'en_attente',
]);

// Dispatcher le job d'envoi
\App\Jobs\SendAccommodationAvailabilityEmail::dispatch($test);

echo "Email de test envoyé à : " . $test->email . "\n";
exit
```

Vous devriez recevoir l'email dans quelques secondes/minutes.

---

## 🔍 Vérifications et debugging

### Vérifier l'état de la queue

```bash
# Voir les jobs en attente
php artisan queue:monitor

# Voir les jobs échoués
php artisan queue:failed

# Rejouer un job échoué
php artisan queue:retry JOB_ID

# Rejouer tous les jobs échoués
php artisan queue:retry all
```

### Vérifier les données en base

```bash
php artisan tinker
```

```php
// Nombre d'hébergements par statut
\App\Models\Accommodation::select('status', \DB::raw('count(*) as total'))
    ->groupBy('status')
    ->get();

// Hébergements qui ont reçu un email
\App\Models\Accommodation::whereNotNull('email_sent_at')
    ->count();

// Hébergements qui ont répondu
\App\Models\Accommodation::whereNotNull('last_response_at')
    ->count();

// Derniers hébergements qui ont répondu
\App\Models\Accommodation::whereNotNull('last_response_at')
    ->orderBy('last_response_at', 'desc')
    ->take(5)
    ->get(['name', 'status', 'last_response_at']);

exit
```

### Tester l'envoi manuel à un seul hébergement

```bash
php artisan tinker
```

```php
// Récupérer un hébergement avec email
$accommodation = \App\Models\Accommodation::whereNotNull('email')
    ->where('email', '!=', '')
    ->first();

// Envoyer l'email
\App\Jobs\SendAccommodationAvailabilityEmail::dispatch($accommodation);

echo "Email envoyé à : " . $accommodation->name . " (" . $accommodation->email . ")\n";
exit
```

### Voir le rendu de l'email sans l'envoyer

```bash
php artisan tinker
```

```php
$accommodation = \App\Models\Accommodation::first();
$token = 'test_token_123';

$html = view('emails.availability-request', [
    'accommodationName' => $accommodation->name,
    'availableUrl' => 'http://localhost:8000/test/available',
    'notAvailableUrl' => 'http://localhost:8000/test/not-available',
])->render();

file_put_contents('email_preview.html', $html);
echo "Aperçu sauvegardé dans email_preview.html\n";
exit
```

Ouvrez `email_preview.html` dans votre navigateur pour voir le rendu.

---

## 📊 Résumé des commandes de test

Voici toutes les commandes dans l'ordre pour un test complet :

```bash
# 1. Vérifier la configuration
cat .env | grep -E 'MAILJET|MAIL_FROM|APP_URL|QUEUE'

# 2. Exécuter les migrations (si pas encore fait)
php artisan migrate

# 3. Vérifier les données
php artisan tinker
\App\Models\Accommodation::whereNotNull('email')->count();
exit

# 4. Lancer le serveur (terminal 1)
php artisan serve

# 5. Lancer le queue worker (terminal 2 - optionnel si QUEUE_CONNECTION=sync)
php artisan queue:work --tries=3 --timeout=90

# 6. Suivre les logs (terminal 3 - optionnel)
tail -f storage/logs/laravel.log

# 7. Ouvrir l'application dans le navigateur
# http://localhost:8000/accommodations

# 8. Vérifier les résultats
php artisan tinker
\App\Models\Accommodation::whereNotNull('email_sent_at')->count();
\App\Models\Accommodation::whereNotNull('last_response_at')->count();
exit
```

---

## 🚨 Problèmes courants et solutions

### Problème 1 : "Class 'Mailjet\Client' not found"

**Solution :**

```bash
composer dump-autoload
php artisan config:clear
php artisan cache:clear
```

### Problème 2 : Les emails ne partent pas

**Vérifications :**

1. Vérifier que les clés Mailjet sont correctes dans `.env`
2. Vérifier les logs : `tail -f storage/logs/laravel.log`
3. Vérifier le queue worker s'il est lancé
4. Tester les clés Mailjet directement :

```bash
php artisan tinker
```

```php
$mailjet = new \Mailjet\Client(
    config('services.mailjet.key'),
    config('services.mailjet.secret'),
    true,
    ['version' => 'v3.1']
);
echo "Connexion OK\n";
exit
```

### Problème 3 : "Token manquant ou invalide"

**Cause :** Le token n'a pas été généré ou a été mal copié

**Solution :**

```bash
php artisan tinker
```

```php
$accommodation = \App\Models\Accommodation::find(1); // Remplacer 1 par l'ID voulu
$token = $accommodation->generateResponseToken();
echo "Nouveau token: " . $token . "\n";
echo "URL: " . route('accommodation.response', ['token' => $token, 'available' => 1]) . "\n";
exit
```

### Problème 4 : Jobs qui échouent

**Voir les détails :**

```bash
php artisan queue:failed
```

**Rejouer un job :**

```bash
php artisan queue:retry JOB_ID
```

**Supprimer les jobs échoués :**

```bash
php artisan queue:flush
```

---

## 📁 Fichiers importants

### Configuration

-   `config/services.php` - Configuration Mailjet
-   `config/mail.php` - Configuration mail
-   `.env` - Variables d'environnement

### Code métier

-   `app/Services/MailjetService.php` - Service d'envoi
-   `app/Jobs/SendAccommodationAvailabilityEmail.php` - Job d'envoi
-   `app/Http/Controllers/AccommodationResponseController.php` - Gestion des réponses
-   `app/Models/Accommodation.php` - Modèle avec méthodes helper

### Vues

-   `resources/views/emails/availability-request.blade.php` - Template email
-   `resources/views/accommodation-response.blade.php` - Page de confirmation

### Migrations

-   `database/migrations/2025_11_07_133310_add_email_tracking_fields_to_accommodations_table.php`
-   `database/migrations/2025_11_07_133347_update_accommodation_status_values.php`

### Routes

-   `routes/web.php` - Route `accommodation.response`

---

## 🎉 Résultat final

Une fois tout configuré, le système fonctionne ainsi :

1. **Admin clique sur "📧 Envoyer les mails"**
   → Les jobs sont mis en queue

2. **Queue worker traite les jobs**
   → Les emails sont envoyés via Mailjet

3. **Hébergeur reçoit l'email**
   → Email avec 2 boutons colorés

4. **Hébergeur clique sur un bouton**
   → Redirection vers la page de confirmation
   → Mise à jour automatique du statut en BDD

5. **Admin voit le statut mis à jour**
   → "Disponible" ou "Indisponible" sur la page accommodations

---

## 📞 Support

En cas de problème, vérifiez :

-   Les logs Laravel : `storage/logs/laravel.log`
-   Les logs de queue : terminal où tourne `queue:work`
-   La documentation Mailjet : https://dev.mailjet.com/
