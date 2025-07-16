# 📧 Guide d'Envoi d'Emails - Référence Rapide

## 🚀 Processus complet d'envoi d'emails

### Méthode 1 : Interface Web (Recommandée)

1. **Aller sur la page** : `/accommodations`
2. **Cliquer sur** : "📧 Envoyer emails"
3. **Confirmer** dans la modal
4. **Traiter les jobs** :
   ```bash
   php artisan queue:work --once --queue=emails
   ```

### Méthode 2 : Ligne de commande

```bash
# 1. Créer les jobs
php artisan accommodation:send-notifications --test

# 2. Traiter les jobs
php artisan queue:work --once --queue=emails
```

## 🔍 Vérifications et Diagnostics

### Vérifier les jobs en attente
```bash
php artisan tinker --execute="echo 'Jobs emails: ' . DB::table('jobs')->where('queue', 'emails')->count();"
```

### Diagnostic complet
```bash
php artisan tinker --execute="
\$pending = DB::table('jobs')->count();
\$emails = DB::table('jobs')->where('queue', 'emails')->count();
\$failed = DB::table('failed_jobs')->count();
echo 'Jobs total: ' . \$pending . PHP_EOL;
echo 'Jobs emails: ' . \$emails . PHP_EOL;
echo 'Jobs failed: ' . \$failed . PHP_EOL;
"
```

### Vérifier les échecs
```bash
php artisan queue:failed
```

## 🧹 Maintenance des Queues

### Vider les jobs en attente
```bash
php artisan queue:clear --queue=emails
```

### Redémarrer les workers
```bash
php artisan queue:restart
```

## ⚠️ Points Critiques

1. **Queue spécifique** : Les emails sont dans la queue `emails`, pas `default`
2. **Commande correcte** : `--queue=emails` est **obligatoire**
3. **Ordre d'exécution** : 
   - D'abord créer les jobs (bouton web ou commande)
   - Ensuite traiter avec le worker
4. **Vérification** : Toujours vérifier les logs après envoi

## 📋 Workflow de Debugging

1. **Cliquer sur bouton** → Jobs créés
2. **Vérifier jobs** : `php artisan tinker --execute="echo DB::table('jobs')->where('queue', 'emails')->count();"`
3. **Traiter jobs** : `php artisan queue:work --once --queue=emails`
4. **Vérifier logs** : `tail -f storage/logs/laravel.log`
5. **Confirmer envoi** : Chercher "Email sent to" dans les logs

## 🎯 Messages de Succès

Logs attendus après envoi réussi :
```
[2025-07-16 12:56:33] local.INFO: Starting accommodation notification emails job
[2025-07-16 12:56:33] local.INFO: Email sent to xxx@example.com
[2025-07-16 12:56:33] local.INFO: Accommodation notification emails job completed: X sent, 0 errors
```

## 🚨 Problèmes Courants

| Problème | Solution |
|----------|----------|
| Jobs ne se traitent pas | Vérifier la queue : `--queue=emails` |
| Pas d'emails reçus | Vérifier les logs d'erreur Mailjet |
| Worker qui s'arrête | Vérifier la configuration Mailjet dans `.env` |
| Database locked | Arrêter les workers multiples : `php artisan queue:restart` |

## 🔧 Configuration Requise

### Fichier .env
```
MAIL_MAILER=mailjet
MAILJET_APIKEY=your-api-key
MAILJET_APISECRET=your-api-secret
MAIL_FROM_ADDRESS="your-verified-email@domain.com"
MAIL_FROM_NAME="Votre Organisation"
```

### Fichier config/services.php
```php
'mailjet' => [
    'key' => env('MAILJET_APIKEY'),
    'secret' => env('MAILJET_APISECRET'),
],
```

---

**✅ Si tu suis ce guide, l'envoi d'emails fonctionnera à coup sûr !**