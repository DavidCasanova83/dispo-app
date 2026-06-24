# Queues, jobs, scheduler et opérations sur fichiers

> Guide opérationnel pour développer des fonctionnalités asynchrones / planifiées dans ce projet **sans supervisor et sans sudo**, en s'appuyant uniquement sur ce qui est déjà en place.

Ce document explique l'architecture d'exécution **réelle** de ce projet, et donne le protocole à suivre pour créer une nouvelle fonctionnalité similaire à la compression PDF (job qui modifie un fichier en arrière-plan).

---

## 1. L'architecture d'exécution

Le projet tourne sous **Apache + PHP-FPM** avec deux contextes utilisateur distincts :

| Contexte | User effectif | Quand |
|---|---|---|
| Requête HTTP (Livewire, contrôleurs) | `www-panelverdon` | Pendant que l'utilisateur navigue |
| Commande CLI (`php artisan ...`) | `panelverdon` | Quand on lance une commande à la main |
| Cron (`* * * * * php artisan schedule:run`) | `panelverdon` | Toutes les minutes |
| Queue worker | `panelverdon` | Lancé par le scheduler (voir §2) |

Donc **tout le code Laravel s'exécute sous l'un de ces deux users**. C'est crucial pour comprendre les permissions de fichiers.

---

## 2. Le scheduler et le queue worker existants

Le projet **n'utilise pas supervisor**. À la place, [bootstrap/app.php](bootstrap/app.php) déclare :

```php
->withSchedule(function ($schedule) {
    // Traiter la queue des emails toutes les minutes
    $schedule->command('queue:work database --stop-when-empty --max-jobs=50 --max-time=50')
        ->everyMinute()
        ->withoutOverlapping(5)
        ->runInBackground()
        ->appendOutputTo(storage_path('logs/mails-scheduler.log'));
    
    // ... autres tâches périodiques
})
```

Combiné au cron de `panelverdon` :

```cron
* * * * * cd /home/panelverdon/panel && php artisan schedule:run >> /dev/null 2>&1
```

Conclusion : **un worker queue tourne déjà chaque minute, sous le user `panelverdon`**. Tu n'as pas besoin de supervisor pour des jobs en queue. Mais ce worker a les permissions de `panelverdon` — pas celles de `www-panelverdon`.

---

## 3. Les permissions de fichiers — la règle à connaître

Les dossiers du projet sont possédés selon **qui les a créés** :

| Dossier | Owner habituel | Pourquoi |
|---|---|---|
| Tout sous `storage/app/public/...` créé par git/composer | `panelverdon:www-panelverdon` (775) | Créé au clone |
| Dossiers créés par PHP-FPM (ex: `storage/app/public/pdfs/`) | `www-panelverdon:www-panelverdon` (755) | Créés au premier upload |
| Dossiers créés en CLI par toi (ex: `storage/app/tmp/` via tinker) | `panelverdon:www-panelverdon` (755) | Créés sous panelverdon |

**Conséquence directe** : un job qui tourne en queue (sous `panelverdon`) **ne peut pas écrire** dans `storage/app/public/pdfs/` (owned by `www-panelverdon` en 755). C'est exactement le piège qu'on a rencontré pendant le développement de la compression PDF.

---

## 4. Choisir la bonne méthode de dispatch

Laravel offre plusieurs façons d'exécuter du code « après » une action. Le choix dépend de **deux questions** :

1. L'action doit-elle modifier un fichier dans un dossier owned by `www-panelverdon` (typiquement `storage/app/public/pdfs/`, `storage/app/public/images/`, etc.) ?
2. Combien de temps dure-t-elle ?

| Méthode | User d'exécution | Bloque la requête ? | Temps OK | Cas d'usage |
|---|---|---|---|---|
| **Code inline** dans Livewire/contrôleur | `www-panelverdon` | Oui | <500 ms | Action quasi-instantanée |
| **`dispatchSync($job)`** | `www-panelverdon` | Oui | <30 s | Action courte qui touche au filesystem `www-panelverdon` |
| **`dispatchAfterResponse($job)`** ⭐ | `www-panelverdon` | Non (envoie réponse avant) | <60 s | Action moyenne qui touche au filesystem `www-panelverdon` (notre cas compression PDF) |
| **`dispatch($job)`** (queue) | `panelverdon` | Non | Jusqu'à plusieurs minutes | Action longue qui ne touche **pas** au filesystem `www-panelverdon` (emails, calculs, API externes) |
| **`Schedule::command(...)`** | `panelverdon` | Non | Plusieurs minutes | Tâche périodique sans contrainte filesystem `www-panelverdon` |

### Arbre de décision

```
Action à exécuter
  │
  ├── Doit modifier un fichier owned by www-panelverdon ?
  │     │
  │     ├── OUI  ─┬── Très rapide ?         → Code inline
  │     │        ├── Moyenne (1-30s) ?      → dispatchSync()
  │     │        └── Longue, après reponse → dispatchAfterResponse() ⭐
  │     │
  │     └── NON  ─┬── Déclenchée par l'utilisateur ?   → dispatch() (queue)
  │              └── Périodique ?                     → Schedule::command(...)
```

⭐ **`dispatchAfterResponse` est la méthode-clé de ce projet** pour les jobs qui doivent toucher au filesystem public sans bloquer l'UX et sans nécessiter supervisor/sudo.

---

## 5. Protocole : créer une nouvelle feature « job + fichier »

Si tu veux faire quelque chose comme la compression PDF (générer une miniature vidéo, convertir une image, OCR un PDF, etc.), suis ce protocole.

### Étape 1 — Service (logique pure, sans Laravel)

Dans [app/Services/](app/Services/), créer une classe qui encapsule la logique métier. Elle ne sait rien de la queue, du modèle, ni de la requête HTTP.

```php
namespace App\Services;

use Illuminate\Support\Str;
use Symfony\Component\Process\Process;

class MaFeatureService
{
    public function execute(string $sourceAbsolutePath): array
    {
        $config = config('ma_feature.params');

        // Pattern recommandé : tmp file dans le même dossier que la source
        // → même filesystem (rename atomique), mêmes permissions
        $tmpPath = dirname($sourceAbsolutePath) . '/.ma-feature-tmp-' . Str::uuid() . '.ext';

        try {
            // Toujours arguments en tableau, jamais shell_exec/exec
            $process = new Process([$config['binary'], '-input', $sourceAbsolutePath, '-output', $tmpPath]);
            $process->setTimeout($config['timeout']);
            $process->run();

            if (!$process->isSuccessful() || !$this->isValidOutput($tmpPath)) {
                @unlink($tmpPath);
                return ['ok' => false, 'reason' => $process->getErrorOutput()];
            }

            // Validation + remplacement atomique
            if (!@rename($tmpPath, $sourceAbsolutePath)) {
                @unlink($tmpPath);
                return ['ok' => false, 'reason' => 'rename_failed'];
            }

            return ['ok' => true];
        } catch (\Throwable $e) {
            @unlink($tmpPath);
            return ['ok' => false, 'reason' => $e->getMessage()];
        }
    }
}
```

**Points à respecter :**
- Tmp file **dans le dossier de la source** (pas de `/tmp`, pas de `storage/app/tmp`) → garantit même filesystem (rename atomique) et mêmes permissions.
- **Arguments tableau** dans `Process` → pas d'injection shell.
- **Cleanup en cas d'erreur** → pas de fichiers temporaires laissés derrière.
- **Validation avant remplacement** → l'original reste intact en cas d'échec.

### Étape 2 — Config

Dans [config/](config/), créer un fichier dédié. Ça permet d'externaliser via `.env` et de garder la logique centralisée.

```php
// config/ma_feature.php
return [
    'params' => [
        'enabled' => env('MA_FEATURE_ENABLED', true),
        'binary'  => env('MA_FEATURE_BIN', '/usr/bin/mon-outil'),
        'timeout' => 170,
        // ... autres paramètres
    ],
];
```

⚠️ Après création d'un fichier `config/*.php`, si tu as un cache config (`bootstrap/cache/config.php`), il faut le rafraîchir :

```bash
php artisan config:clear   # en dev
php artisan config:cache   # en prod après déploiement (cf clearcache.md)
```

### Étape 3 — Job

Dans [app/Jobs/](app/Jobs/), créer le wrapper qui sera dispatché.

```php
namespace App\Jobs;

use App\Models\MonModele;
use App\Services\MaFeatureService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class MaFeatureJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 2;
    public int $timeout = 180;
    public int $backoff = 30;

    public function __construct(
        public int $modelId,
        public string $relativePath  // ex: "uploads/foo.bin"
    ) {}

    public function handle(MaFeatureService $service): void
    {
        // Idempotence : vérifier que l'état n'a pas bougé depuis le dispatch
        $model = MonModele::find($this->modelId);
        if (!$model || $model->some_path !== $this->relativePath) {
            return;
        }
        if (!Storage::disk('public')->exists($this->relativePath)) {
            return;
        }

        $absolute = Storage::disk('public')->path($this->relativePath);
        $result = $service->execute($absolute);

        Log::info('MaFeatureJob done', [
            'model_id' => $this->modelId,
            'result' => $result,
        ]);

        // Optionnel : synchroniser la DB avec l'état réel après l'opération
        $newSize = @filesize($absolute);
        if ($newSize !== false && (int) $model->size !== $newSize) {
            $model->update(['size' => $newSize]);
        }
    }

    public function failed(\Throwable $e): void
    {
        Log::error('MaFeatureJob failed', [
            'model_id' => $this->modelId,
            'exception' => $e->getMessage(),
        ]);
    }
}
```

**Points à respecter :**
- **`tries`, `timeout`, `backoff`** définis dans le job — pas dans la config queue (réutilisable).
- **Idempotence en début de `handle()`** : vérifier que la DB est dans l'état attendu, sinon return.
- **`failed()`** définie pour logger sans crash silencieux.
- Si l'opération touche au filesystem `www-panelverdon`, **prévoir le dispatch via `dispatchAfterResponse`** (pas `dispatch()`) — voir étape 4.

### Étape 4 — Le dispatch (le choix qui change tout)

Dans le contrôleur ou le composant Livewire qui déclenche l'action :

```php
// Cas 1 : l'action modifie un fichier dans pdfs/, images/, etc.
// → contexte PHP-FPM obligatoire → dispatchAfterResponse
MaFeatureJob::dispatchAfterResponse($model->id, $relativePath);

// Cas 2 : l'action est un envoi d'email, une API externe, du calcul DB pur
// → la queue gérée par le scheduler convient → dispatch + afterCommit
MaFeatureJob::dispatch($model->id, $relativePath)->afterCommit();
```

**Différence concrète** :
- `dispatchAfterResponse` : le job s'exécute **dans le même process PHP-FPM** qui a servi la requête (donc sous `www-panelverdon`), juste après l'envoi de la réponse au navigateur.
- `dispatch` : le job est placé dans la table `jobs`, traité plus tard par le `queue:work` du scheduler (sous `panelverdon`).

---

## 6. Tâches périodiques (cron)

Pour une tâche qui doit s'exécuter à intervalle régulier, deux possibilités selon où tu la déclares :

```php
// Dans routes/console.php
Schedule::command('ma:commande')->dailyAt('03:00');

// OU dans bootstrap/app.php (style ->withSchedule)
$schedule->command('ma:commande')
    ->dailyAt('03:00')
    ->withoutOverlapping()
    ->appendOutputTo(storage_path('logs/ma-commande.log'));
```

Les deux marchent. Le cron `schedule:run` (sous `panelverdon`) les déclenche.

**Si la commande touche au filesystem `www-panelverdon`** : il faut soit lancer la commande via une route HTTP (cf §7), soit accepter le coût d'un `sudo -u www-panelverdon` en exception (pour des one-shots manuels).

---

## 7. Lancer une commande artisan sous `www-panelverdon` (sans sudo)

Cas typique : tu as une commande artisan qui doit toucher au filesystem public (ex: `brochures:compress-existing`), mais le user CLI `panelverdon` n'a pas les droits.

**Solution sans sudo** : déclencher la commande via une route HTTP admin protégée.

```php
// routes/web.php
Route::middleware(['auth', 'role:admin'])->post('/admin/run-compress', function () {
    Artisan::call('brochures:compress-existing', ['--limit' => 10]);
    return back()->with('success', 'Lancement OK — voir les logs');
});
```

L'admin clique le bouton → la requête arrive sur PHP-FPM (sous `www-panelverdon`) → `Artisan::call(...)` exécute la commande dans **ce process**, donc avec les bons droits.

**Limitations** :
- Bloque la requête le temps de l'exécution → utiliser `--limit` pour fragmenter, ou `dispatchAfterResponse` à l'intérieur.
- Pour de très longs traitements, il faut soit augmenter le `max_execution_time` PHP-FPM, soit dispatcher plusieurs jobs.

---

## 8. Pièges fréquents à éviter

| Piège | Conséquence | Solution |
|---|---|---|
| `rename()` entre `/tmp` et `storage/app/public/` | Échec `EXDEV` si filesystems différents | Tmp file dans le **même dossier** que la cible |
| `shell_exec("cmd $userInput")` | Injection shell | `Symfony\Component\Process\Process` avec **arguments tableau** |
| `dispatch()` après un `update()` DB | Le worker peut lire la DB avant le commit | `->afterCommit()` |
| `dispatch()` pour une action sur `pdfs/` | Échec de permissions (worker sous panelverdon) | `dispatchAfterResponse` |
| Modif de `config/*.php` sans `config:clear` | Le code charge l'ancienne config (`null` souvent) | `php artisan config:clear` (dev), `config:cache` (prod) |
| Pas de filtre extension dans `EditsBrochures` | Tentative de compresser un jpg/png | `str_ends_with(strtolower($path), '.pdf')` |
| Pas de `failed()` sur le job | Erreur silencieuse | Toujours définir `failed(\Throwable $e)` |
| Écriture directe sur la cible avant validation | Fichier corrompu visible publiquement si crash | Tmp + validation + `rename()` atomique |

---

## 9. Commandes de debug utiles

```bash
# Voir les jobs en attente dans la queue
php artisan tinker --execute='echo DB::table("jobs")->count() . " job(s) pending" . PHP_EOL;'

# Voir les failed jobs
php artisan tinker --execute='
foreach (DB::table("failed_jobs")->orderByDesc("id")->limit(5)->get() as $f) {
    $p = json_decode($f->payload, true);
    echo "#" . $f->id . " " . ($p["displayName"] ?? "?") . " — " . $f->failed_at . PHP_EOL;
    echo substr($f->exception, 0, 200) . PHP_EOL;
}'

# Voir les logs en temps réel
tail -f storage/logs/laravel.log
tail -f storage/logs/laravel.log | grep -i "ma-feature"

# Identifier le user qui possède un dossier
stat -c "%n: owner=%U group=%G perms=%a" storage/app/public/pdfs

# Vérifier si un user CLI peut écrire dans un dossier
touch storage/app/public/pdfs/_test 2>&1 && rm -f storage/app/public/pdfs/_test || echo "CANNOT write"

# Forcer le traitement d'un job en sync (utile pour debug)
php artisan tinker --execute='App\Jobs\MaFeatureJob::dispatchSync(1, "uploads/foo.bin");'

# Voir tous les processus liés à Laravel
ps -eo user,pid,etime,cmd | grep -E "artisan|queue|schedule" | grep -v grep
```

---

## 10. Checklist avant de déployer une nouvelle feature

- [ ] Service créé dans `app/Services/`, configurable via `config()`
- [ ] Job créé dans `app/Jobs/` avec `tries`, `timeout`, `backoff`, `failed()`
- [ ] Idempotence vérifiée en début de `handle()`
- [ ] **Dispatch via `dispatchAfterResponse` si l'action touche au filesystem `www-panelverdon`**
- [ ] Tmp file dans le **même dossier** que la cible
- [ ] **Arguments tableau** pour les commandes externes (pas de shell)
- [ ] Validation du résultat avant remplacement
- [ ] Cleanup en cas d'erreur
- [ ] Log structuré (`Log::info` + contexte)
- [ ] `php artisan config:clear` après ajout d'un fichier de config
- [ ] Testé avec un fichier réel via `dispatchSync()`
- [ ] Branché dans **tous** les points d'entrée (création + modification + remplacement)
- [ ] Si le code doit compresser/modifier des fichiers existants : commande artisan dédiée avec `--dry-run` par défaut

---

## 11. Référence : exemple complet implémenté

La compression PDF de brochures suit exactement ce protocole. Voir :

- Service : [app/Services/PdfCompressionService.php](app/Services/PdfCompressionService.php)
- Job : [app/Jobs/CompressBrochurePdf.php](app/Jobs/CompressBrochurePdf.php)
- Config : [config/brochures.php](config/brochures.php)
- Dispatch (création) : [app/Livewire/Admin/ImageManager.php](app/Livewire/Admin/ImageManager.php#L478)
- Dispatch (modification user) : [app/Livewire/MyBrochuresManager.php](app/Livewire/MyBrochuresManager.php#L433)
- Dispatch (modification admin) : [app/Livewire/Concerns/EditsBrochures.php](app/Livewire/Concerns/EditsBrochures.php#L281)
- Commande artisan one-shot (resync) : [app/Console/Commands/ResyncBrochureSizes.php](app/Console/Commands/ResyncBrochureSizes.php)
- Commande artisan one-shot (rétro-compression) : [app/Console/Commands/CompressExistingBrochures.php](app/Console/Commands/CompressExistingBrochures.php)
