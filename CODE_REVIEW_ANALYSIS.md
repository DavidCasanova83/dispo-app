# Analyse de Code - DISPO-APP

## Table des matières

1. [Résumé des technologies](#1-résumé-des-technologies)
2. [Points positifs et bonnes pratiques](#2-points-positifs-et-bonnes-pratiques)
3. [Problèmes critiques à corriger](#3-problèmes-critiques-à-corriger)
4. [Violations des conventions Laravel MVC](#4-violations-des-conventions-laravel-mvc)
5. [Problèmes de qualité de code](#5-problèmes-de-qualité-de-code)
6. [Problèmes de sécurité](#6-problèmes-de-sécurité)
7. [Problèmes de performance](#7-problèmes-de-performance)
8. [Recommandations architecturales](#8-recommandations-architecturales)
9. [Plan d'action prioritaire](#9-plan-daction-prioritaire)

---

## 1. Résumé des technologies

### Stack technique

| Catégorie | Technologie | Version |
|-----------|-------------|---------|
| **Framework** | Laravel | 12.x |
| **PHP** | PHP | 8.2+ |
| **Frontend réactif** | Livewire | 3.x |
| **UI Components** | Livewire Flux | 2.1 |
| **CSS Framework** | Tailwind CSS | 4.x |
| **Component Library** | DaisyUI | 5.x |
| **Build Tool** | Vite | 6.x |
| **Base de données** | SQLite / MySQL | - |
| **Queue** | Database Driver | - |

### Packages principaux

| Package | Usage |
|---------|-------|
| `spatie/laravel-permission` | Gestion des rôles et permissions (RBAC) |
| `mailjet/mailjet-apiv3-php` | Service d'envoi d'emails |
| `maatwebsite/excel` | Export vers Excel |
| `intervention/image` | Traitement d'images et thumbnails |
| `livewire/flux` | Composants UI pré-construits |
| `spatie/laravel-honeypot` | Protection anti-spam (honeypot) |
| `coderflex/laravel-turnstile` | CAPTCHA Cloudflare |
| `stevebauman/purify` | Sanitisation HTML |
| `fgribreau/mailchecker` | Validation emails jetables |

### Architecture de l'application

```
dispo-app/
├── app/
│   ├── Console/Commands/     # 9 commandes Artisan
│   ├── Exports/              # 1 export Excel
│   ├── Http/
│   │   ├── Controllers/      # 6 contrôleurs (3 API, 3 Web)
│   │   ├── Middleware/       # 4 middlewares custom
│   │   └── Resources/        # 1 resource API
│   ├── Jobs/                 # 4 jobs asynchrones
│   ├── Livewire/             # 28 composants Livewire
│   ├── Models/               # 16 modèles Eloquent
│   ├── Policies/             # 3 policies d'autorisation
│   ├── Rules/                # 2 règles de validation custom
│   └── Services/             # 5 services métier
├── database/
│   └── migrations/           # 28 migrations
└── resources/views/          # 60+ templates Blade
```

---

## 2. Points positifs et bonnes pratiques

### ✅ Ce qui est bien fait

#### Architecture et organisation

1. **Utilisation appropriée de Livewire** : Les composants Livewire sont utilisés pour l'interactivité, ce qui est cohérent avec le choix technologique.

2. **Système de permissions bien implémenté** : L'utilisation de `spatie/laravel-permission` avec des rôles (Super-admin, Admin, Qualification, etc.) et permissions granulaires.

3. **Policies d'autorisation** : Les fichiers `ImagePolicy`, `AgendaPolicy` et `ImageOrderPolicy` implémentent correctement l'autorisation au niveau des modèles.

4. **Soft Deletes** : Implémenté sur les modèles `Image` et `Agenda`, permettant la récupération de données supprimées.

5. **Système d'approbation des utilisateurs** : Un workflow d'approbation des nouveaux utilisateurs est en place avec middleware `EnsureUserIsApproved`.

#### Sécurité

6. **Protection anti-spam multiple** :
   - Honeypot (`spatie/laravel-honeypot`)
   - CAPTCHA Turnstile (Cloudflare)
   - Validation emails jetables (`NotDisposableEmail`)
   - Détection de contenu spam (`NoSpamContent`)

7. **Sanitisation HTML** : Utilisation de `stevebauman/purify` pour nettoyer les entrées utilisateur.

8. **Rate limiting** : Implémenté sur les uploads et formulaires de commande.

9. **Token de vérification WordPress** : Middleware `VerifyWordPressApiToken` pour sécuriser le webhook.

#### Base de données

10. **Utilisation des casts** : Les modèles utilisent correctement les casts pour les types de données (`datetime`, `boolean`, `array`, `integer`).

11. **Index de base de données** : Présents sur les colonnes fréquemment recherchées.

12. **Transactions DB** : Utilisées dans `PublicImageOrderForm::submitOrder()` pour garantir l'intégrité des données.

---

## 3. Problèmes critiques à corriger

### 🔴 CRITIQUE #1 : Composant Livewire "God Object"

**Fichier** : `app/Livewire/Admin/ImageManager.php` (924 lignes)

**Problème** : Ce composant fait tout et viole le principe de responsabilité unique (SRP). Il gère :
- Upload d'images
- Édition d'images
- Suppression d'images
- CRUD des catégories
- CRUD des auteurs
- CRUD des secteurs
- Gestion des images par défaut
- Gestion des signalements

**Pourquoi c'est un problème** :
- Difficile à maintenir et tester
- Risque élevé d'effets de bord lors de modifications
- Temps de chargement allongé
- Confusion entre responsabilités

**Solution recommandée** :

```php
// Diviser en plusieurs composants spécialisés :

// app/Livewire/Admin/Images/ImageUploader.php
class ImageUploader extends Component
{
    public function uploadImages() { /* ... */ }
}

// app/Livewire/Admin/Images/ImageEditor.php
class ImageEditor extends Component
{
    public function updateImage() { /* ... */ }
}

// app/Livewire/Admin/Images/ImageList.php
class ImageList extends Component
{
    public function render() { /* ... */ }
}

// app/Livewire/Admin/Categories/CategoryManager.php
class CategoryManager extends Component
{
    public function addCategory() { /* ... */ }
    public function deleteCategory() { /* ... */ }
}

// app/Livewire/Admin/Reports/ReportManager.php
class ReportManager extends Component
{
    public function resolveReport() { /* ... */ }
}
```

---

### 🔴 CRITIQUE #2 : Logique métier dans les routes

**Fichier** : `routes/web.php:58-79`

**Code problématique** :
```php
Route::get('accommodations', function () {
    $accommodations = \App\Models\Accommodation::orderBy('name')->get();

    // 20+ lignes de calcul de statistiques...
    $stats = [
        'total' => $accommodations->count(),
        'by_status' => $accommodations->groupBy('status')->map->count(),
        // ...
    ];

    return view('accommodations', compact('accommodations', 'stats', 'topCities'));
})->name('accommodations');
```

**Pourquoi c'est un problème** :
- Les routes ne doivent contenir AUCUNE logique métier
- Violation du pattern MVC
- Code non-testable
- Impossible de réutiliser cette logique

**Solution recommandée** :

```php
// routes/web.php
Route::get('accommodations', [AccommodationController::class, 'index'])
    ->name('accommodations');

// app/Http/Controllers/AccommodationController.php
class AccommodationController extends Controller
{
    public function __construct(
        private AccommodationStatisticsService $statisticsService
    ) {}

    public function index(): View
    {
        $accommodations = Accommodation::orderBy('name')->get();
        $stats = $this->statisticsService->calculate($accommodations);
        $topCities = $this->statisticsService->getTopCities($accommodations);

        return view('accommodations', compact('accommodations', 'stats', 'topCities'));
    }
}

// app/Services/AccommodationStatisticsService.php
class AccommodationStatisticsService
{
    public function calculate(Collection $accommodations): array
    {
        return [
            'total' => $accommodations->count(),
            'by_status' => $accommodations->groupBy('status')->map->count(),
            // ...
        ];
    }
}
```

---

### 🔴 CRITIQUE #3 : Email hardcodé dans le code

**Fichier** : `app/Services/MailjetService.php:629-634`

**Code problématique** :
```php
'To' => [
    [
        'Email' => 'webmaster@verdontourisme.com',  // ❌ Hardcodé !
        'Name' => 'Webmaster',
    ],
],
```

**Pourquoi c'est un problème** :
- Impossible de changer sans modifier le code
- Pas de flexibilité par environnement (dev/staging/prod)
- Violation du principe de configuration

**Solution recommandée** :

```php
// config/services.php
'notifications' => [
    'brochure_report_email' => env('BROCHURE_REPORT_EMAIL', 'webmaster@example.com'),
],

// .env
BROCHURE_REPORT_EMAIL=webmaster@verdontourisme.com

// MailjetService.php
'To' => [
    [
        'Email' => config('services.notifications.brochure_report_email'),
        'Name' => 'Webmaster',
    ],
],
```

---

### 🔴 CRITIQUE #4 : Duplication massive de code

**Fichier** : `app/Services/MailjetService.php`

**Problème** : Chaque méthode d'envoi d'email (8 méthodes) répète le même pattern try/catch avec logging identique (environ 40-50 lignes dupliquées par méthode).

**Code dupliqué** :
```php
try {
    $response = $this->mailjet->post(Resources::$Email, ['body' => $body]);

    if ($response->success()) {
        Log::info("... email sent successfully to {$email}", [...]);
        return ['success' => true, 'data' => $response->getData()];
    }

    Log::error("Failed to send ... email to {$email}", [...]);
    return ['success' => false, 'error' => $response->getReasonPhrase()];
} catch (\Exception $e) {
    Log::error("Exception while sending ... email to {$email}", [...]);
    return ['success' => false, 'error' => $e->getMessage()];
}
```

**Solution recommandée** :

```php
class MailjetService
{
    /**
     * Méthode générique pour envoyer un email
     */
    protected function send(array $body, string $context, array $logContext = []): array
    {
        try {
            $response = $this->mailjet->post(Resources::$Email, ['body' => $body]);

            if ($response->success()) {
                Log::info("[Mailjet] {$context} - Success", array_merge($logContext, [
                    'response' => $response->getData(),
                ]));
                return ['success' => true, 'data' => $response->getData()];
            }

            Log::error("[Mailjet] {$context} - Failed", array_merge($logContext, [
                'status' => $response->getStatus(),
                'reason' => $response->getReasonPhrase(),
            ]));

            return ['success' => false, 'error' => $response->getReasonPhrase()];

        } catch (\Exception $e) {
            Log::error("[Mailjet] {$context} - Exception", array_merge($logContext, [
                'exception' => $e->getMessage(),
            ]));

            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    /**
     * Exemple d'utilisation simplifiée
     */
    public function sendUserApprovalEmail(string $toEmail, string $toName): array
    {
        $body = $this->buildEmailBody(
            to: [['Email' => $toEmail, 'Name' => $toName]],
            subject: "Votre compte a été approuvé",
            textPart: "Bonjour {$toName}...",
            htmlPart: $this->generateUserApprovalEmailHtml($toName, $toEmail, url('/login'))
        );

        return $this->send($body, 'User Approval', ['email' => $toEmail]);
    }

    protected function buildEmailBody(array $to, string $subject, string $textPart, string $htmlPart): array
    {
        return [
            'Messages' => [[
                'From' => [
                    'Email' => config('mail.from.address'),
                    'Name' => config('mail.from.name'),
                ],
                'To' => $to,
                'Subject' => $subject,
                'TextPart' => $textPart,
                'HTMLPart' => $htmlPart,
            ]],
        ];
    }
}
```

---

### 🔴 CRITIQUE #5 : Tokens sans expiration

**Fichier** : `app/Models/Accommodation.php:103-108`

**Code problématique** :
```php
public function generateResponseToken(): string
{
    $token = bin2hex(random_bytes(32));
    $this->update(['email_response_token' => $token]);
    return $token;
}
```

**Pourquoi c'est un problème** :
- Les tokens d'email n'expirent jamais
- Un lien de réponse peut être utilisé des années après son envoi
- Risque de sécurité si l'email est compromis

**Solution recommandée** :

```php
// Migration
Schema::table('accommodations', function (Blueprint $table) {
    $table->timestamp('email_token_expires_at')->nullable()->after('email_response_token');
});

// Accommodation.php
public function generateResponseToken(int $expirationHours = 72): string
{
    $token = bin2hex(random_bytes(32));
    $this->update([
        'email_response_token' => $token,
        'email_token_expires_at' => now()->addHours($expirationHours),
    ]);
    return $token;
}

public function isTokenValid(?string $token): bool
{
    if (!$token || $token !== $this->email_response_token) {
        return false;
    }

    if ($this->email_token_expires_at && $this->email_token_expires_at->isPast()) {
        return false;
    }

    return true;
}

// AccommodationResponseController.php
public function handleResponse(Request $request)
{
    $accommodation = Accommodation::where('email_response_token', $request->token)->first();

    if (!$accommodation || !$accommodation->isTokenValid($request->token)) {
        abort(403, 'Ce lien a expiré ou est invalide.');
    }
    // ...
}
```

---

## 4. Violations des conventions Laravel MVC

### 🟠 VIOLATION #1 : Absence de Form Requests

**Problème** : La validation se fait directement dans les composants Livewire au lieu d'utiliser des Form Requests.

**Fichier exemple** : `app/Livewire/Admin/ImageManager.php:111-122`

```php
protected $rules = [
    'contentFiles.*' => 'required|mimes:pdf,jpg,jpeg,png|max:51200',
    'presentationImages.*' => 'nullable|image|max:10240',
];
```

**Pourquoi c'est un problème** :
- Règles de validation non réutilisables
- Logique de validation mélangée avec la logique du composant
- Difficile à tester isolément

**Solution recommandée** :

```php
// app/Http/Requests/StoreImageRequest.php
class StoreImageRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('create', Image::class);
    }

    public function rules(): array
    {
        return [
            'contentFiles.*' => ['required', 'mimes:pdf,jpg,jpeg,png', 'max:51200'],
            'presentationImages.*' => ['nullable', 'image', 'max:10240'],
            'title' => ['nullable', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:1000'],
        ];
    }

    public function messages(): array
    {
        return [
            'contentFiles.*.required' => 'Le fichier de contenu est obligatoire.',
            'contentFiles.*.mimes' => 'Le fichier doit être un PDF ou une image (JPG, PNG).',
        ];
    }
}
```

---

### 🟠 VIOLATION #2 : Modèle "Fat Model"

**Fichier** : `app/Livewire/QualificationForm.php`

**Problème** : Le composant contient des données métier qui devraient être dans une configuration ou un service.

```php
protected function initializeOptions()
{
    $this->specificOptions = [
        'annot' => ['Escalade', 'Train à Vapeur', 'Grès d\'Annot'],
        'colmars-les-alpes' => ['Lac d\'Allos', 'Cascade de la Lance', 'Maison Musée'],
        // ...
    ];

    $this->generalOptions = [
        'Randonnées', 'Pêche', 'Train', 'Sports',
        // ...
    ];
}
```

**Solution recommandée** :

```php
// config/qualification.php
return [
    'cities' => [
        'annot' => [
            'name' => 'Annot',
            'specific_options' => ['Escalade', 'Train à Vapeur', 'Grès d\'Annot'],
        ],
        'colmars-les-alpes' => [
            'name' => 'Colmars-les-Alpes',
            'specific_options' => ['Lac d\'Allos', 'Cascade de la Lance', 'Maison Musée'],
        ],
    ],
    'general_options' => [
        'Randonnées', 'Pêche', 'Train', 'Sports',
        // ...
    ],
];

// Utilisation
$specificOptions = config("qualification.cities.{$city}.specific_options");
$generalOptions = config('qualification.general_options');
```

---

### 🟠 VIOLATION #3 : Absence de Repository Pattern

**Problème** : Les requêtes Eloquent sont éparpillées dans les contrôleurs et composants.

**Exemple** dans `QualificationForm.php:132-136` :
```php
$draft = Qualification::where('user_id', $userId)
    ->where('city', $this->city)
    ->where('completed', false)
    ->latest()
    ->first();
```

**Solution recommandée** :

```php
// app/Repositories/QualificationRepository.php
class QualificationRepository
{
    public function findDraft(int $userId, string $city): ?Qualification
    {
        return Qualification::query()
            ->where('user_id', $userId)
            ->where('city', $city)
            ->incomplete()
            ->latest()
            ->first();
    }

    public function createDraft(array $data): Qualification
    {
        return Qualification::create($data);
    }

    public function updateDraft(Qualification $qualification, array $data): bool
    {
        return $qualification->update($data);
    }

    public function getStatsByCity(string $city): array
    {
        return [
            'total' => Qualification::forCity($city)->count(),
            'completed' => Qualification::forCity($city)->completed()->count(),
            'incomplete' => Qualification::forCity($city)->incomplete()->count(),
        ];
    }
}

// Utilisation via injection de dépendance
public function __construct(
    private QualificationRepository $qualificationRepository
) {}

public function loadDraft(): void
{
    $draft = $this->qualificationRepository->findDraft(Auth::id(), $this->city);
    // ...
}
```

---

### 🟠 VIOLATION #4 : Logique d'upload dans le composant

**Fichier** : `app/Livewire/Admin/ImageManager.php:132-397`

**Problème** : 265 lignes de code pour la méthode `uploadImages()` qui gère :
- Validation MIME
- Validation extension
- Génération de noms de fichiers
- Stockage des fichiers
- Création de thumbnails
- Gestion des images par défaut
- Création en base de données
- Appel Artisan

**Solution recommandée** : Créer un service dédié

```php
// app/Services/ImageUploadService.php
class ImageUploadService
{
    private const ALLOWED_CONTENT_MIME_TYPES = [
        'application/pdf',
        'image/jpeg',
        'image/png',
    ];

    public function __construct(
        private ImageProcessingService $imageProcessor,
        private ThumbnailService $thumbnailService
    ) {}

    public function upload(UploadedFile $file, array $metadata, ?UploadedFile $presentationImage = null): Image
    {
        $this->validateMimeType($file);

        $contentPath = $this->storeContentFile($file);
        $presentationPath = $this->handlePresentationImage($file, $presentationImage, $metadata);
        $thumbnailPath = $this->thumbnailService->generate($presentationPath);

        return Image::create([
            'path' => $presentationPath,
            'pdf_path' => $contentPath,
            'thumbnail_path' => $thumbnailPath,
            ...$metadata,
        ]);
    }

    private function validateMimeType(UploadedFile $file): void
    {
        if (!in_array($file->getMimeType(), self::ALLOWED_CONTENT_MIME_TYPES)) {
            throw new InvalidMimeTypeException($file->getMimeType());
        }
    }

    // ...
}
```

---

## 5. Problèmes de qualité de code

### 🟡 QUALITÉ #1 : Variables publiques excessives dans Livewire

**Fichier** : `app/Livewire/Admin/ImageManager.php:28-90`

**Problème** : 50+ propriétés publiques, rendant le composant difficile à comprendre et maintenir.

```php
public $contentFiles = [];
public $presentationImages = [];
public $search = '';
public $showDeleteModal = false;
public $selectedImage = null;
public $titles = [];
public $altTexts = [];
public $descriptions = [];
// ... 40+ autres propriétés
```

**Solution recommandée** : Utiliser des objets de transfert de données (DTO)

```php
// app/Livewire/Admin/ImageManager.php
class ImageManager extends Component
{
    public ImageUploadState $uploadState;
    public ImageEditState $editState;
    public ImageSearchState $searchState;

    public function mount()
    {
        $this->uploadState = new ImageUploadState();
        $this->editState = new ImageEditState();
        $this->searchState = new ImageSearchState();
    }
}

// app/Livewire/States/ImageUploadState.php
class ImageUploadState
{
    public array $contentFiles = [];
    public array $presentationImages = [];
    public array $titles = [];
    public array $descriptions = [];
    // ...

    public function reset(): void
    {
        $this->contentFiles = [];
        $this->presentationImages = [];
        // ...
    }
}
```

---

### 🟡 QUALITÉ #2 : Absence de typage strict

**Problème** : Paramètres et retours de méthodes non typés dans plusieurs fichiers.

**Exemple** dans `MailjetService.php:216` :
```php
public function sendNewUserNotification(string $toEmail, string $toName, $newUser): array
//                                                                          ^^^^^^ Type manquant
```

**Solution recommandée** :
```php
public function sendNewUserNotification(string $toEmail, string $toName, User $newUser): array
```

---

### 🟡 QUALITÉ #3 : Nombres magiques

**Fichier** : `app/Livewire/Admin/ImageManager.php`

```php
RateLimiter::attempt('upload-images:' . auth()->id(), 10, function() {}, 60);
//                                                    ^^              ^^
//                                          Nombres magiques non documentés
```

**Solution recommandée** :
```php
private const MAX_UPLOADS_PER_MINUTE = 10;
private const RATE_LIMIT_DECAY_SECONDS = 60;

RateLimiter::attempt(
    key: 'upload-images:' . auth()->id(),
    maxAttempts: self::MAX_UPLOADS_PER_MINUTE,
    callback: fn() => null,
    decaySeconds: self::RATE_LIMIT_DECAY_SECONDS
);
```

---

### 🟡 QUALITÉ #4 : Commentaires en français incohérents

**Problème** : Mix de commentaires en français et anglais, code en anglais.

```php
/**
 * Supprimer le fichier physique quand le model est supprimé  // Français
 */
protected static function booted(): void  // Code anglais
{
    // Supprimer l'image principale  // Français
}
```

**Recommandation** : Choisir une langue et s'y tenir (de préférence l'anglais pour le code, français acceptable pour la documentation utilisateur).

---

### 🟡 QUALITÉ #5 : Indentation et formatage incohérents

**Fichier** : `app/Http/Controllers/QualificationController.php`

```php
class QualificationController extends Controller
{
  /**                          // ❌ 2 espaces
   * Display the qualification...
   */
  public function index(): View  // ❌ 2 espaces
  {
    return view(...);           // ❌ 4 espaces (devrait être 8)
  }
}
```

**Solution** : Configurer PHP-CS-Fixer ou Laravel Pint avec les règles PSR-12.

```bash
# Installation de Laravel Pint (déjà inclus dans Laravel 12)
./vendor/bin/pint

# Ou créer pint.json pour personnaliser
{
    "preset": "laravel",
    "rules": {
        "concat_space": {
            "spacing": "one"
        }
    }
}
```

---

## 6. Problèmes de sécurité

### 🔒 SÉCURITÉ #1 : Logs de données sensibles

**Fichier** : `app/Livewire/PublicImageOrderForm.php:212-222`

```php
logger('🔐 Turnstile Configuration:', [
    'site_key' => config('turnstile.turnstile_site_key'),
    'secret_key_preview' => substr(config('turnstile.turnstile_secret_key'), 0, 10) . '...',
    //                              ^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^
    //                              ⚠️ Partie de la clé secrète loggée !
]);
```

**Problème** : Même une partie de la clé secrète ne devrait JAMAIS être loggée.

**Solution** :
```php
logger('Turnstile Configuration:', [
    'site_key_configured' => !empty(config('turnstile.turnstile_site_key')),
    'secret_key_configured' => !empty(config('turnstile.turnstile_secret_key')),
]);
```

---

### 🔒 SÉCURITÉ #2 : Validation insuffisante des fichiers uploadés

**Fichier** : `app/Livewire/Admin/ImageManager.php:162-174`

**Problème** : La validation MIME type peut être contournée car elle se base sur l'extension.

```php
// Validation de l'extension réelle
$allowedExtensions = ['pdf', 'jpg', 'jpeg', 'png'];
$extension = strtolower($contentFile->getClientOriginalExtension());
if (!in_array($extension, $allowedExtensions)) {
    // ...
}
```

**Solution recommandée** : Vérifier le contenu réel du fichier.

```php
// Utiliser le MIME type détecté par le contenu, pas l'extension
$realMimeType = mime_content_type($contentFile->getRealPath());

// Ou utiliser la méthode plus robuste de Laravel
$mimeType = $contentFile->getMimeType(); // Basé sur le contenu

// Vérification croisée
if (!in_array($mimeType, self::ALLOWED_DOWNLOAD_MIME_TYPES)) {
    throw new \Exception('Type de fichier non autorisé.');
}

// Vérification supplémentaire pour les images
if (str_starts_with($mimeType, 'image/')) {
    $imageInfo = getimagesize($contentFile->getRealPath());
    if ($imageInfo === false) {
        throw new \Exception('Fichier image corrompu.');
    }
}
```

---

### 🔒 SÉCURITÉ #3 : Absence de validation CSRF sur les routes API

**Fichier** : `routes/api.php`

**Problème** : Les routes API publiques n'ont pas de protection contre les attaques CSRF si appelées depuis un navigateur.

**Solution** : Ajouter une vérification de l'origine ou un token API.

```php
// Pour l'API WordPress, c'est déjà fait avec VerifyWordPressApiToken
// Pour les autres API publiques, envisager un rate limiting plus strict

Route::middleware(['throttle:api'])->group(function () {
    Route::get('/images', [ImageApiController::class, 'index']);
});
```

---

### 🔒 SÉCURITÉ #4 : Stockage de données sensibles en clair

**Fichier** : `app/Models/ImageOrder.php` (supposé)

**Problème** : Les données personnelles des clients (adresse, téléphone) sont stockées en clair.

**Solution recommandée** : Chiffrer les données sensibles.

```php
// app/Models/ImageOrder.php
protected $casts = [
    'address_line1' => 'encrypted',
    'address_line2' => 'encrypted',
    'phone_number' => 'encrypted',
    'customer_notes' => 'encrypted',
];
```

---

## 7. Problèmes de performance

### ⚡ PERFORMANCE #1 : N+1 Query Problem

**Fichier** : `app/Livewire/Admin/ImageManager.php:876-880`

```php
$this->usedDisplayOrders = Image::whereNotNull('display_order')
    ->orderBy('display_order')
    ->pluck('display_order')
    ->unique()
    ->values()
    ->toArray();
```

Puis plus bas :
```php
$query = Image::with(['uploader', 'category', 'author', 'sector', 'responsable'])
```

**Problème** : Deux requêtes séparées au lieu d'une seule optimisée.

**Solution** :
```php
// Utiliser une sous-requête ou un scope
$query = Image::with(['uploader', 'category', 'author', 'sector', 'responsable'])
    ->selectRaw('*, (SELECT GROUP_CONCAT(DISTINCT display_order) FROM images WHERE display_order IS NOT NULL) as used_orders')
```

Ou mieux, utiliser le caching :
```php
$this->usedDisplayOrders = Cache::remember('used_display_orders', 300, function () {
    return Image::whereNotNull('display_order')
        ->pluck('display_order')
        ->unique()
        ->values()
        ->toArray();
});
```

---

### ⚡ PERFORMANCE #2 : Requêtes non paginées

**Fichier** : `routes/web.php:58`

```php
$accommodations = \App\Models\Accommodation::orderBy('name')->get();
// ⚠️ Charge TOUS les hébergements en mémoire
```

**Solution** :
```php
// Utiliser la pagination
$accommodations = Accommodation::orderBy('name')->paginate(50);

// Ou pour les statistiques, utiliser des agrégations SQL
$stats = [
    'total' => Accommodation::count(),
    'by_status' => Accommodation::groupBy('status')
        ->selectRaw('status, count(*) as count')
        ->pluck('count', 'status'),
];
```

---

### ⚡ PERFORMANCE #3 : Regénération JSON à chaque modification

**Fichier** : `app/Livewire/Admin/ImageManager.php:389`

```php
// Après chaque upload/update/delete
Artisan::call('images:generate-json');
```

**Problème** : Appel synchrone à chaque modification, bloquant l'utilisateur.

**Solution** :
```php
// Option 1: Utiliser un job asynchrone
GenerateImagesJson::dispatch()->delay(now()->addSeconds(5));

// Option 2: Utiliser un event listener
// ImageCreated, ImageUpdated, ImageDeleted events
// -> RegenerateImagesJsonListener (avec debounce)

// Option 3: Utiliser le cache au lieu d'un fichier JSON statique
// Et invalider le cache lors des modifications
Cache::forget('public_images_json');
```

---

### ⚡ PERFORMANCE #4 : Absence d'indexation optimale

**Fichier** : Les migrations

**Problème** : Certaines requêtes fréquentes n'ont pas d'index composites.

**Solution** :
```php
// Migration pour optimiser les requêtes fréquentes
Schema::table('qualifications', function (Blueprint $table) {
    // Index composite pour les brouillons par utilisateur et ville
    $table->index(['user_id', 'city', 'completed'], 'qualifications_draft_lookup');
});

Schema::table('images', function (Blueprint $table) {
    // Index pour le tri par display_order
    $table->index(['display_order', 'created_at'], 'images_display_sort');

    // Index pour les images disponibles
    $table->index(['quantity_available', 'print_available'], 'images_available');
});
```

---

## 8. Recommandations architecturales

### 📐 ARCHITECTURE #1 : Implémenter une architecture en couches

```
┌─────────────────────────────────────────────────────────────┐
│                    PRESENTATION LAYER                        │
│  ┌──────────────┐  ┌──────────────┐  ┌──────────────────┐  │
│  │  Controllers  │  │   Livewire   │  │  API Resources   │  │
│  └──────────────┘  └──────────────┘  └──────────────────┘  │
└─────────────────────────────────────────────────────────────┘
                              │
                              ▼
┌─────────────────────────────────────────────────────────────┐
│                    APPLICATION LAYER                         │
│  ┌──────────────┐  ┌──────────────┐  ┌──────────────────┐  │
│  │   Services    │  │    Actions   │  │  Form Requests   │  │
│  └──────────────┘  └──────────────┘  └──────────────────┘  │
└─────────────────────────────────────────────────────────────┘
                              │
                              ▼
┌─────────────────────────────────────────────────────────────┐
│                      DOMAIN LAYER                            │
│  ┌──────────────┐  ┌──────────────┐  ┌──────────────────┐  │
│  │    Models    │  │ Repositories │  │  Domain Events   │  │
│  └──────────────┘  └──────────────┘  └──────────────────┘  │
└─────────────────────────────────────────────────────────────┘
                              │
                              ▼
┌─────────────────────────────────────────────────────────────┐
│                   INFRASTRUCTURE LAYER                       │
│  ┌──────────────┐  ┌──────────────┐  ┌──────────────────┐  │
│  │   Database   │  │  Mail/Queue  │  │   File Storage   │  │
│  └──────────────┘  └──────────────┘  └──────────────────┘  │
└─────────────────────────────────────────────────────────────┘
```

---

### 📐 ARCHITECTURE #2 : Utiliser le pattern Action

Au lieu de services monolithiques, utiliser des Actions (Single Responsibility) :

```php
// app/Actions/Images/UploadImage.php
class UploadImage
{
    public function __construct(
        private ImageProcessingService $imageProcessor,
        private ThumbnailGenerator $thumbnailGenerator
    ) {}

    public function execute(UploadedFile $file, array $metadata): Image
    {
        // Une seule responsabilité : uploader une image
    }
}

// app/Actions/Images/DeleteImage.php
class DeleteImage
{
    public function execute(Image $image): void
    {
        // Une seule responsabilité : supprimer une image
    }
}

// Utilisation dans Livewire
public function uploadImages(UploadImage $action)
{
    foreach ($this->files as $file) {
        $action->execute($file, $this->metadata);
    }
}
```

---

### 📐 ARCHITECTURE #3 : Implémenter les Events/Listeners

```php
// app/Events/ImageUploaded.php
class ImageUploaded
{
    public function __construct(public Image $image) {}
}

// app/Listeners/GenerateThumbnail.php
class GenerateThumbnail
{
    public function handle(ImageUploaded $event): void
    {
        // Générer le thumbnail de manière asynchrone
    }
}

// app/Listeners/InvalidateImageCache.php
class InvalidateImageCache
{
    public function handle(ImageUploaded|ImageUpdated|ImageDeleted $event): void
    {
        Cache::forget('public_images_json');
        Cache::forget('images_list');
    }
}

// EventServiceProvider
protected $listen = [
    ImageUploaded::class => [
        GenerateThumbnail::class,
        InvalidateImageCache::class,
        NotifyAdminOfNewImage::class,
    ],
];
```

---

### 📐 ARCHITECTURE #4 : Configuration centralisée

Créer un fichier de configuration dédié à l'application :

```php
// config/dispo.php
return [
    'cities' => [
        'annot' => [
            'name' => 'Annot',
            'specific_options' => ['Escalade', 'Train à Vapeur', 'Grès d\'Annot'],
        ],
        // ...
    ],

    'uploads' => [
        'max_file_size' => 51200, // KB
        'allowed_mime_types' => ['application/pdf', 'image/jpeg', 'image/png'],
        'rate_limit' => [
            'max_attempts' => 10,
            'decay_seconds' => 60,
        ],
    ],

    'email' => [
        'token_expiration_hours' => 72,
        'brochure_report_recipient' => env('BROCHURE_REPORT_EMAIL'),
    ],

    'orders' => [
        'notification_email' => env('ORDERS_NOTIFICATION_EMAIL'),
        'max_items_per_order' => 20,
    ],
];
```

---

### 📐 ARCHITECTURE #5 : Implémenter les tests

L'application n'a actuellement aucun test significatif. Voici une structure recommandée :

```
tests/
├── Unit/
│   ├── Models/
│   │   ├── ImageTest.php
│   │   ├── QualificationTest.php
│   │   └── AccommodationTest.php
│   ├── Services/
│   │   ├── MailjetServiceTest.php
│   │   └── ImageUploadServiceTest.php
│   └── Rules/
│       ├── NotDisposableEmailTest.php
│       └── NoSpamContentTest.php
├── Feature/
│   ├── Auth/
│   │   ├── RegistrationTest.php
│   │   └── ApprovalWorkflowTest.php
│   ├── Admin/
│   │   ├── ImageManagerTest.php
│   │   └── UserManagementTest.php
│   ├── Qualification/
│   │   ├── QualificationFormTest.php
│   │   └── QualificationExportTest.php
│   └── Api/
│       ├── ImageApiTest.php
│       └── ContactFormWebhookTest.php
└── Browser/ (Dusk)
    ├── OrderFormTest.php
    └── QualificationFormTest.php
```

Exemple de test :

```php
// tests/Feature/Admin/ImageManagerTest.php
class ImageManagerTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_upload_image(): void
    {
        $admin = User::factory()->create();
        $admin->assignRole('Admin');

        Storage::fake('public');

        Livewire::actingAs($admin)
            ->test(ImageManager::class)
            ->set('contentFiles', [UploadedFile::fake()->create('test.pdf', 1024)])
            ->set('titles.0', 'Test Brochure')
            ->call('uploadImages')
            ->assertHasNoErrors()
            ->assertDispatched('image-uploaded');

        $this->assertDatabaseHas('images', [
            'title' => 'Test Brochure',
            'uploaded_by' => $admin->id,
        ]);
    }

    public function test_user_cannot_delete_others_images(): void
    {
        $user1 = User::factory()->create();
        $user2 = User::factory()->create();
        $user1->assignRole('Admin');
        $user2->assignRole('Admin');

        $image = Image::factory()->create(['uploaded_by' => $user1->id]);

        Livewire::actingAs($user2)
            ->test(ImageManager::class)
            ->call('deleteImage', $image->id)
            ->assertForbidden();
    }
}
```

---

## 9. Plan d'action prioritaire

### Phase 1 : Corrections urgentes (1-2 semaines)

| # | Tâche | Priorité | Effort |
|---|-------|----------|--------|
| 1 | Corriger les emails hardcodés → configuration | 🔴 Critique | 1h |
| 2 | Ajouter expiration aux tokens d'email | 🔴 Critique | 2h |
| 3 | Supprimer le log de la clé secrète Turnstile | 🔴 Critique | 5min |
| 4 | Extraire la logique métier des routes | 🔴 Critique | 3h |
| 5 | Configurer Laravel Pint pour le formatage | 🟠 Important | 1h |

### Phase 2 : Refactoring structurel (2-4 semaines)

| # | Tâche | Priorité | Effort |
|---|-------|----------|--------|
| 6 | Diviser ImageManager en composants spécialisés | 🔴 Critique | 1-2 jours |
| 7 | Créer un ImageUploadService | 🟠 Important | 4h |
| 8 | Refactoriser MailjetService (réduire duplication) | 🟠 Important | 3h |
| 9 | Créer des Form Requests pour la validation | 🟠 Important | 4h |
| 10 | Implémenter le Repository Pattern | 🟡 Souhaitable | 1 jour |

### Phase 3 : Optimisations (4-6 semaines)

| # | Tâche | Priorité | Effort |
|---|-------|----------|--------|
| 11 | Ajouter le caching pour les requêtes fréquentes | 🟠 Important | 4h |
| 12 | Optimiser les index de base de données | 🟠 Important | 2h |
| 13 | Convertir la génération JSON en job asynchrone | 🟡 Souhaitable | 2h |
| 14 | Paginer les listes volumineuses | 🟡 Souhaitable | 3h |
| 15 | Chiffrer les données sensibles des commandes | 🟠 Important | 2h |

### Phase 4 : Qualité et tests (ongoing)

| # | Tâche | Priorité | Effort |
|---|-------|----------|--------|
| 16 | Écrire les tests unitaires des modèles | 🟠 Important | 2 jours |
| 17 | Écrire les tests feature des composants Livewire | 🟠 Important | 3 jours |
| 18 | Configurer l'intégration continue (CI) | 🟡 Souhaitable | 4h |
| 19 | Documenter l'API avec OpenAPI/Swagger | 🟡 Souhaitable | 1 jour |
| 20 | Créer le fichier CONTRIBUTING.md | 🟡 Souhaitable | 2h |

---

## Conclusion

Cette application est fonctionnelle et implémente correctement plusieurs bonnes pratiques Laravel (permissions, policies, soft deletes, sanitisation). Cependant, elle souffre de plusieurs problèmes architecturaux courants dans les projets qui ont grandi organiquement :

1. **Composants "God Objects"** qui font trop de choses
2. **Logique métier mal placée** (dans les routes, les composants)
3. **Duplication de code significative** (MailjetService)
4. **Absence de tests** rendant le refactoring risqué

La priorité devrait être :
1. **Corriger les problèmes de sécurité** (tokens, logs sensibles)
2. **Extraire la logique des routes** vers les contrôleurs
3. **Diviser les gros composants** en unités plus petites
4. **Ajouter des tests** avant tout refactoring majeur

Le code montre une bonne compréhension des fonctionnalités Laravel, mais manque de rigueur sur les principes SOLID et les patterns architecturaux. L'investissement dans le refactoring maintenant évitera une dette technique bien plus coûteuse à l'avenir.

---

*Document généré le 27 décembre 2025*
*Analyse réalisée sur la base de code DISPO-APP*
