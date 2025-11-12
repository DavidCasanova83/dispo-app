# Automatisation du fetch Apidae avec Laravel Task Scheduler

Ce guide explique comment automatiser la récupération des hébergements Apidae une fois par jour à 5h du matin en production.

## Table des matières

1. [Introduction](#introduction)
2. [Prérequis](#prérequis)
3. [Étape 1 : Configuration du code Laravel](#étape-1--configuration-du-code-laravel)
4. [Étape 2 : Configuration du serveur](#étape-2--configuration-du-serveur)
5. [Étape 3 : Tests](#étape-3--tests)
6. [Options avancées](#options-avancées)
7. [Monitoring et logs](#monitoring-et-logs)
8. [Dépannage](#dépannage)
9. [Exemples de planifications alternatives](#exemples-de-planifications-alternatives)

---

## Introduction

Laravel Task Scheduler permet de planifier des tâches récurrentes directement dans le code de votre application. Au lieu de créer plusieurs entrées cron, vous n'avez besoin que d'une seule entrée qui exécute le scheduler Laravel.

**Avantages :**
- Configuration centralisée dans le code
- Logs automatiques
- Prévention des exécutions simultanées
- Notifications en cas d'échec
- Facilité de test et de maintenance
- Versionnement avec Git

---

## Prérequis

- Laravel 12 (ou supérieur)
- Accès SSH au serveur de production
- Droits pour modifier le crontab
- Commande `apidae:fetch` fonctionnelle (voir [APIDAE_SETUP.md](APIDAE_SETUP.md))

---

## Étape 1 : Configuration du code Laravel

### 1.1 Modifier le fichier `routes/console.php`

Dans Laravel 12, les tâches planifiées se configurent dans le fichier `routes/console.php`.

Ouvrez le fichier et ajoutez le code suivant :

```php
<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

// Commande d'exemple (vous pouvez la garder ou la supprimer)
Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// ===== PLANIFICATION APIDAE =====
// Récupération automatique des hébergements Apidae tous les jours à 5h du matin
Schedule::command('apidae:fetch --all')
    ->dailyAt('05:00')                    // Exécution tous les jours à 5h00
    ->withoutOverlapping()                // Empêche les exécutions simultanées
    ->runInBackground()                   // Exécute en arrière-plan
    ->onOneServer()                       // S'exécute sur un seul serveur (si vous avez plusieurs serveurs)
    ->appendOutputTo(storage_path('logs/apidae-scheduler.log')); // Log des sorties
```

### 1.2 Explication des options

| Option | Description |
|--------|-------------|
| `dailyAt('05:00')` | Exécute la tâche tous les jours à 5h00 |
| `withoutOverlapping()` | Empêche le lancement d'une nouvelle exécution si la précédente n'est pas terminée |
| `runInBackground()` | Lance la commande en arrière-plan pour ne pas bloquer le scheduler |
| `onOneServer()` | Si vous avez plusieurs serveurs, la tâche ne s'exécute que sur un seul |
| `appendOutputTo()` | Enregistre la sortie de la commande dans un fichier de log |

### 1.3 Commiter les changements

```bash
git add routes/console.php
git commit -m "Ajout planification automatique fetch Apidae à 5h"
```

---

## Étape 2 : Configuration du serveur

### 2.1 Comprendre le fonctionnement

Laravel Task Scheduler nécessite **une seule entrée cron** qui s'exécute **chaque minute**. Cette commande vérifie quelles tâches doivent être exécutées et les lance au bon moment.

### 2.2 Ajouter l'entrée cron sur le serveur

Connectez-vous à votre serveur en SSH et éditez le crontab :

```bash
ssh utilisateur@votre-serveur.com
crontab -e
```

Ajoutez cette ligne à la fin du fichier :

```bash
* * * * * cd /chemin/vers/votre/projet && php artisan schedule:run >> /dev/null 2>&1
```

**Remplacez** `/chemin/vers/votre/projet` par le chemin réel de votre application Laravel.

**Exemple :**
```bash
* * * * * cd /var/www/dispo-app && php artisan schedule:run >> /dev/null 2>&1
```

### 2.3 Vérifier le chemin PHP

Si `php` n'est pas trouvé, utilisez le chemin complet :

```bash
which php
# Résultat : /usr/bin/php
```

Utilisez ce chemin dans votre crontab :

```bash
* * * * * cd /var/www/dispo-app && /usr/bin/php artisan schedule:run >> /dev/null 2>&1
```

### 2.4 Sauvegarder et quitter

Dans l'éditeur crontab :
- Appuyez sur `Ctrl + X` (pour nano)
- Appuyez sur `Y` pour confirmer
- Appuyez sur `Entrée`

### 2.5 Vérifier que le cron est bien enregistré

```bash
crontab -l
```

Vous devriez voir votre entrée cron affichée.

---

## Étape 3 : Tests

### 3.1 Test en local (développement)

**Ne configurez PAS le cron en local**. Testez manuellement :

```bash
# Tester la commande directement
php artisan apidae:fetch --all

# Tester le scheduler manuellement (exécute toutes les tâches dues maintenant)
php artisan schedule:run

# Voir toutes les tâches planifiées et leur prochain horaire d'exécution
php artisan schedule:list
```

### 3.2 Forcer l'exécution d'une tâche en local

Pour tester sans attendre 5h du matin, modifiez temporairement la planification dans `routes/console.php` :

```php
// Configuration temporaire pour test
Schedule::command('apidae:fetch --all')
    ->everyMinute()  // Au lieu de ->dailyAt('05:00')
    ->withoutOverlapping()
    ->runInBackground()
    ->appendOutputTo(storage_path('logs/apidae-scheduler.log'));
```

Puis exécutez :

```bash
php artisan schedule:run
```

**N'oubliez pas de remettre `->dailyAt('05:00')` après le test !**

### 3.3 Test en production

Après avoir configuré le cron sur le serveur, vérifiez les logs le lendemain matin (après 5h) :

```bash
# Sur le serveur
tail -f storage/logs/apidae-scheduler.log
```

Vous devriez voir les sorties de la commande `apidae:fetch --all`.

---

## Options avancées

### 4.1 Notifications en cas d'échec (Email)

Pour recevoir un email si la tâche échoue, ajoutez :

```php
use Illuminate\Support\Facades\Mail;

Schedule::command('apidae:fetch --all')
    ->dailyAt('05:00')
    ->withoutOverlapping()
    ->runInBackground()
    ->onOneServer()
    ->appendOutputTo(storage_path('logs/apidae-scheduler.log'))
    ->onFailure(function () {
        // Envoyer un email en cas d'échec
        Mail::raw('La synchronisation Apidae a échoué !', function ($message) {
            $message->to('admin@example.com')
                    ->subject('Erreur synchronisation Apidae');
        });
    });
```

**Prérequis :** Configurez vos paramètres email dans `.env` (MAIL_MAILER, MAIL_HOST, etc.)

### 4.2 Notifications Slack

```php
use Illuminate\Support\Facades\Http;

Schedule::command('apidae:fetch --all')
    ->dailyAt('05:00')
    ->withoutOverlapping()
    ->runInBackground()
    ->onOneServer()
    ->appendOutputTo(storage_path('logs/apidae-scheduler.log'))
    ->onSuccess(function () {
        Http::post('https://hooks.slack.com/services/VOTRE/WEBHOOK/URL', [
            'text' => '✅ Synchronisation Apidae réussie !'
        ]);
    })
    ->onFailure(function () {
        Http::post('https://hooks.slack.com/services/VOTRE/WEBHOOK/URL', [
            'text' => '❌ Échec de la synchronisation Apidae'
        ]);
    });
```

### 4.3 Exécution uniquement en production

Pour éviter que la tâche s'exécute en local ou en staging :

```php
Schedule::command('apidae:fetch --all')
    ->dailyAt('05:00')
    ->when(function () {
        return app()->environment('production'); // Seulement en prod
    })
    ->withoutOverlapping()
    ->runInBackground()
    ->onOneServer()
    ->appendOutputTo(storage_path('logs/apidae-scheduler.log'));
```

### 4.4 Timeout et retry

Ajouter un timeout de 10 minutes et un retry en cas d'échec :

```php
Schedule::command('apidae:fetch --all')
    ->dailyAt('05:00')
    ->withoutOverlapping()
    ->runInBackground()
    ->onOneServer()
    ->timeout(600) // Timeout de 10 minutes (600 secondes)
    ->appendOutputTo(storage_path('logs/apidae-scheduler.log'));
```

---

## Monitoring et logs

### 5.1 Vérifier les logs du scheduler

Laravel enregistre automatiquement les exécutions du scheduler :

```bash
# Logs généraux de Laravel
tail -f storage/logs/laravel.log

# Logs spécifiques Apidae (si vous avez utilisé appendOutputTo)
tail -f storage/logs/apidae-scheduler.log
```

### 5.2 Lister toutes les tâches planifiées

```bash
php artisan schedule:list
```

Résultat attendu :
```
0 5 * * * ................ apidae:fetch --all
```

### 5.3 Tester manuellement le scheduler

```bash
# Exécute toutes les tâches qui sont "dues" maintenant
php artisan schedule:run

# Voir ce qui sera exécuté sans l'exécuter
php artisan schedule:test
```

### 5.4 Vérifier que le cron système fonctionne

Sur le serveur, vérifiez les logs système du cron :

```bash
# Debian/Ubuntu
sudo tail -f /var/log/syslog | grep CRON

# CentOS/RHEL
sudo tail -f /var/log/cron
```

Vous devriez voir une ligne chaque minute qui exécute `schedule:run`.

---

## Dépannage

### 6.1 La tâche ne s'exécute pas

**Vérifications :**

1. **Le cron système est-il configuré ?**
   ```bash
   crontab -l
   ```

2. **Le chemin vers le projet est-il correct ?**
   ```bash
   cd /chemin/vers/votre/projet
   php artisan schedule:list
   ```

3. **PHP est-il accessible ?**
   ```bash
   which php
   /usr/bin/php -v
   ```

4. **Les permissions sont-elles correctes ?**
   ```bash
   ls -la storage/logs/
   chmod -R 775 storage/logs/
   ```

5. **La tâche est-elle listée ?**
   ```bash
   php artisan schedule:list
   ```

### 6.2 La tâche s'exécute plusieurs fois

Utilisez `->withoutOverlapping()` pour éviter les doublons :

```php
Schedule::command('apidae:fetch --all')
    ->dailyAt('05:00')
    ->withoutOverlapping(10); // Maximum 10 minutes d'attente
```

### 6.3 La tâche échoue silencieusement

Vérifiez les logs :

```bash
tail -100 storage/logs/laravel.log
tail -100 storage/logs/apidae-scheduler.log
```

Ajoutez des logs de débogage :

```php
Schedule::command('apidae:fetch --all')
    ->dailyAt('05:00')
    ->before(function () {
        \Log::info('Début de la synchronisation Apidae');
    })
    ->after(function () {
        \Log::info('Fin de la synchronisation Apidae');
    });
```

### 6.4 Variables d'environnement manquantes

Le cron peut ne pas avoir accès aux mêmes variables d'environnement. Assurez-vous que votre `.env` est bien lu :

```bash
# Dans le crontab, spécifiez le chemin complet
* * * * * cd /var/www/dispo-app && /usr/bin/php artisan schedule:run >> /dev/null 2>&1
```

Ou chargez explicitement les variables :

```bash
* * * * * cd /var/www/dispo-app && /usr/bin/php -d variables_order=EGPCS artisan schedule:run >> /dev/null 2>&1
```

---

## Exemples de planifications alternatives

### 7.1 Toutes les heures

```php
Schedule::command('apidae:fetch --all')
    ->hourly()
    ->withoutOverlapping();
```

### 7.2 Tous les lundis à 8h

```php
Schedule::command('apidae:fetch --all')
    ->weeklyOn(1, '08:00') // 1 = Lundi
    ->withoutOverlapping();
```

### 7.3 Deux fois par jour (matin et soir)

```php
Schedule::command('apidae:fetch --all')
    ->twiceDaily(5, 17) // 5h et 17h
    ->withoutOverlapping();
```

### 7.4 Toutes les 6 heures

```php
Schedule::command('apidae:fetch --all')
    ->cron('0 */6 * * *') // À 0h, 6h, 12h, 18h
    ->withoutOverlapping();
```

### 7.5 Du lundi au vendredi uniquement

```php
Schedule::command('apidae:fetch --all')
    ->dailyAt('05:00')
    ->weekdays() // Seulement en semaine
    ->withoutOverlapping();
```

### 7.6 Le premier jour de chaque mois

```php
Schedule::command('apidae:fetch --all')
    ->monthlyOn(1, '05:00') // Le 1er de chaque mois à 5h
    ->withoutOverlapping();
```

### 7.7 Syntaxe cron personnalisée

```php
Schedule::command('apidae:fetch --all')
    ->cron('0 5 * * *') // Tous les jours à 5h (syntaxe cron classique)
    ->withoutOverlapping();
```

**Format cron :** `* * * * *`
```
┬ ┬ ┬ ┬ ┬
│ │ │ │ │
│ │ │ │ └─── Jour de la semaine (0 - 6) (Dimanche = 0)
│ │ │ └───── Mois (1 - 12)
│ │ └─────── Jour du mois (1 - 31)
│ └───────── Heure (0 - 23)
└─────────── Minute (0 - 59)
```

---

## Commandes utiles

```bash
# Lister toutes les tâches planifiées
php artisan schedule:list

# Exécuter manuellement le scheduler
php artisan schedule:run

# Tester ce qui serait exécuté (sans l'exécuter)
php artisan schedule:test

# Exécuter la commande directement (sans le scheduler)
php artisan apidae:fetch --all

# Voir les logs en temps réel
tail -f storage/logs/apidae-scheduler.log

# Vérifier le crontab
crontab -l

# Éditer le crontab
crontab -e
```

---

## Résumé de la configuration en production

1. **Code Laravel** (`routes/console.php`) :
   ```php
   Schedule::command('apidae:fetch --all')
       ->dailyAt('05:00')
       ->withoutOverlapping()
       ->runInBackground()
       ->onOneServer()
       ->appendOutputTo(storage_path('logs/apidae-scheduler.log'));
   ```

2. **Crontab serveur** :
   ```bash
   * * * * * cd /var/www/dispo-app && php artisan schedule:run >> /dev/null 2>&1
   ```

3. **Vérification** :
   ```bash
   php artisan schedule:list
   tail -f storage/logs/apidae-scheduler.log
   ```

---

## Ressources

- [Documentation officielle Laravel Task Scheduling](https://laravel.com/docs/12.x/scheduling)
- [Documentation Apidae](APIDAE_SETUP.md)
- [Crontab Guru](https://crontab.guru/) - Outil pour tester vos expressions cron
