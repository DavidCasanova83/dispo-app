<?php

namespace App\Livewire\Admin;

use App\Jobs\SendNewAgendaNotification;
use App\Models\Agenda;
use App\Models\Author;
use App\Models\Category;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Storage;
use Intervention\Image\Drivers\Gd\Driver;
use Intervention\Image\ImageManager as InterventionImageManager;
use Livewire\Component;
use Livewire\Features\SupportFileUploads\WithFileUploads;
use Livewire\WithPagination;

class AgendaManager extends Component
{
    use WithFileUploads, WithPagination;

    // Cover image upload
    public $coverImage = null;

    // Agenda settings (category/author)
    public $agendaCategoryId = null;
    public $agendaAuthorId = null;

    // Agenda upload properties
    public $pdfFile = null;
    public $title = '';
    public $description = '';
    public $startDate = '';
    public $endDate = '';

    // Edit properties
    public $showEditModal = false;
    public $editingAgenda = null;
    public $editTitle = '';
    public $editDescription = '';
    public $editStartDate = '';
    public $editEndDate = '';
    public $editPdfFile = null;

    // Delete properties
    public $showDeleteModal = false;
    public $selectedAgenda = null;

    // Whitelist des MIME types autorisés
    private const ALLOWED_IMAGE_MIME_TYPES = [
        'image/jpeg',
        'image/png',
        'image/gif',
        'image/webp',
    ];

    private const ALLOWED_PDF_MIME_TYPES = [
        'application/pdf',
    ];

    protected $messages = [
        'coverImage.image' => 'Le fichier doit être une image.',
        'coverImage.max' => 'L\'image ne doit pas dépasser 10 MB.',
        'pdfFile.required' => 'Le fichier PDF est obligatoire.',
        'pdfFile.mimes' => 'Le fichier doit être un PDF.',
        'pdfFile.max' => 'Le PDF ne doit pas dépasser 50 MB.',
        'startDate.required' => 'La date de début est obligatoire.',
        'endDate.required' => 'La date de fin est obligatoire.',
        'endDate.after_or_equal' => 'La date de fin doit être après ou égale à la date de début.',
    ];

    /**
     * Initialisation du composant
     */
    public function mount()
    {
        // Charger les paramètres de l'agenda courant
        $currentAgenda = Agenda::current()->first();
        if ($currentAgenda) {
            $this->agendaCategoryId = $currentAgenda->category_id;
            $this->agendaAuthorId = $currentAgenda->author_id;
        }

        // Définir les valeurs par défaut si non définies
        if (!$this->agendaCategoryId) {
            $defaultCategory = Category::where('name', 'Agendas')->first();
            $this->agendaCategoryId = $defaultCategory?->id;
        }

        if (!$this->agendaAuthorId) {
            $defaultAuthor = Author::where('name', 'Verdon Tourisme')->first();
            $this->agendaAuthorId = $defaultAuthor?->id;
        }
    }

    /**
     * Mettre à jour les paramètres de l'agenda (catégorie/auteur)
     */
    public function updateAgendaSettings()
    {
        $this->authorize('create', Agenda::class);

        $this->validate([
            'agendaCategoryId' => 'nullable|exists:categories,id',
            'agendaAuthorId' => 'nullable|exists:authors,id',
        ]);

        Log::info('[AGENDA] Mise à jour paramètres agenda - Début', [
            'user_id' => auth()->id(),
            'category_id' => $this->agendaCategoryId,
            'author_id' => $this->agendaAuthorId,
        ]);

        // Mettre à jour l'agenda courant
        $currentAgenda = Agenda::current()->first();
        if ($currentAgenda) {
            $currentAgenda->update([
                'category_id' => $this->agendaCategoryId ?: null,
                'author_id' => $this->agendaAuthorId ?: null,
            ]);
            Log::info('[AGENDA] Mise à jour paramètres agenda - Succès', [
                'agenda_id' => $currentAgenda->id,
                'category_id' => $this->agendaCategoryId,
                'author_id' => $this->agendaAuthorId,
            ]);
            session()->flash('success', 'Paramètres de l\'agenda mis à jour avec succès.');
        } else {
            Log::warning('[AGENDA] Mise à jour paramètres agenda - Aucun agenda en cours');
            session()->flash('error', 'Aucun agenda en cours. Uploadez d\'abord un agenda.');
        }
    }

    /**
     * Upload de l'image de couverture globale
     */
    public function uploadCoverImage()
    {
        Log::info('[AGENDA] Upload image couverture - Début', [
            'user_id' => auth()->id(),
        ]);

        // Rate limiting
        $executed = RateLimiter::attempt(
            'upload-cover:' . auth()->id(),
            5,
            function () {},
            60
        );

        if (!$executed) {
            Log::warning('[AGENDA] Upload image couverture - Rate limit atteint', [
                'user_id' => auth()->id(),
            ]);
            session()->flash('error', 'Trop de tentatives d\'upload. Veuillez attendre avant de réessayer.');
            return;
        }

        $this->authorize('create', Agenda::class);

        $this->validate([
            'coverImage' => 'required|image|max:10240', // 10MB max
        ]);

        try {
            // Valider MIME type
            if (!in_array($this->coverImage->getMimeType(), self::ALLOWED_IMAGE_MIME_TYPES)) {
                Log::warning('[AGENDA] Upload image couverture - MIME type invalide', [
                    'user_id' => auth()->id(),
                    'mime_type' => $this->coverImage->getMimeType(),
                ]);
                session()->flash('error', 'Type d\'image non autorisé.');
                return;
            }

            // Valider extension
            $allowedExtensions = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
            $extension = strtolower($this->coverImage->getClientOriginalExtension());
            if (!in_array($extension, $allowedExtensions)) {
                Log::warning('[AGENDA] Upload image couverture - Extension invalide', [
                    'user_id' => auth()->id(),
                    'extension' => $extension,
                ]);
                session()->flash('error', 'Extension d\'image non autorisée.');
                return;
            }

            Log::info('[AGENDA] Upload image couverture - Validation OK', [
                'mime_type' => $this->coverImage->getMimeType(),
                'size' => $this->coverImage->getSize(),
                'extension' => $extension,
            ]);

            // S'assurer que le dossier existe
            $agendaDir = Storage::disk('public')->path('agendas');
            if (!file_exists($agendaDir)) {
                mkdir($agendaDir, 0755, true);
                Log::info('[AGENDA FILE] Dossier agendas créé');
            }

            // Sauvegarder l'image avec un nom temporaire puis la traiter
            $tempPath = $this->coverImage->storeAs('agendas', 'temp_cover.' . $extension, 'public');
            $tempFullPath = Storage::disk('public')->path($tempPath);

            // Compression et redimensionnement
            $manager = new InterventionImageManager(new Driver());
            $img = $manager->read($tempFullPath);

            // Sauvegarder l'image principale comme couverture.jpg
            $coverFullPath = Storage::disk('public')->path(Agenda::COVER_IMAGE_PATH);
            $img->save($coverFullPath, quality: 85);

            Log::info('[AGENDA FILE] Image couverture sauvegardée', [
                'path' => Agenda::COVER_IMAGE_PATH,
            ]);

            // Créer le thumbnail
            $thumbnailFullPath = Storage::disk('public')->path(Agenda::COVER_THUMBNAIL_PATH);
            $thumbnail = $manager->read($tempFullPath);
            $thumbnail->cover(300, 300);
            $thumbnail->save($thumbnailFullPath, quality: 80);

            Log::info('[AGENDA FILE] Thumbnail couverture créé', [
                'path' => Agenda::COVER_THUMBNAIL_PATH,
            ]);

            // Supprimer le fichier temporaire
            Storage::disk('public')->delete($tempPath);

            Log::info('[AGENDA] Upload image couverture - Succès', [
                'user_id' => auth()->id(),
            ]);

            session()->flash('success', 'Image de couverture mise à jour avec succès.');
            $this->reset(['coverImage']);

        } catch (\Exception $e) {
            Log::error('[AGENDA] Upload image couverture - Erreur', [
                'user_id' => auth()->id(),
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            session()->flash('error', 'Erreur lors de l\'upload: ' . $e->getMessage());
        }
    }

    /**
     * Upload d'un nouvel agenda (PDF uniquement)
     * L'agenda sera créé avec le statut "pending" et sera activé automatiquement à sa date de début
     */
    public function uploadAgenda()
    {
        Log::info('[AGENDA] Upload nouvel agenda - Début', [
            'user_id' => auth()->id(),
            'title' => $this->title,
            'start_date' => $this->startDate,
            'end_date' => $this->endDate,
        ]);

        // Rate limiting
        $executed = RateLimiter::attempt(
            'upload-agenda:' . auth()->id(),
            5,
            function () {},
            60
        );

        if (!$executed) {
            Log::warning('[AGENDA] Upload agenda - Rate limit atteint', [
                'user_id' => auth()->id(),
            ]);
            session()->flash('error', 'Trop de tentatives d\'upload. Veuillez attendre avant de réessayer.');
            return;
        }

        $this->authorize('create', Agenda::class);

        // Vérifier qu'il n'y a pas déjà un agenda en attente
        if (Agenda::pending()->exists()) {
            Log::warning('[AGENDA] Upload agenda - Un agenda pending existe déjà', [
                'user_id' => auth()->id(),
            ]);
            session()->flash('error', 'Un agenda est déjà en attente. Vous devez le supprimer ou attendre son activation avant d\'en ajouter un nouveau.');
            return;
        }

        $this->validate([
            'pdfFile' => 'required|mimes:pdf|max:51200', // 50MB max
            'title' => 'nullable|string|max:255',
            'description' => 'nullable|string|max:1000',
            'startDate' => 'required|date',
            'endDate' => 'required|date|after_or_equal:startDate',
        ]);

        try {
            // Valider MIME type PDF
            if (!in_array($this->pdfFile->getMimeType(), self::ALLOWED_PDF_MIME_TYPES)) {
                Log::warning('[AGENDA] Upload agenda - MIME type invalide', [
                    'user_id' => auth()->id(),
                    'mime_type' => $this->pdfFile->getMimeType(),
                ]);
                session()->flash('error', 'Type de fichier PDF non autorisé.');
                return;
            }

            Log::info('[AGENDA] Upload agenda - Validation OK', [
                'mime_type' => $this->pdfFile->getMimeType(),
                'size' => $this->pdfFile->getSize(),
                'filename' => $this->pdfFile->getClientOriginalName(),
            ]);

            // Sauvegarder le PDF dans le dossier pending
            $pdfFilename = $this->pdfFile->getClientOriginalName();

            // S'assurer que les dossiers existent
            $agendaDir = Storage::disk('public')->path('agendas');
            if (!file_exists($agendaDir)) {
                mkdir($agendaDir, 0755, true);
                Log::info('[AGENDA FILE] Dossier agendas créé');
            }
            $pendingDir = Storage::disk('public')->path('agendas/pending');
            if (!file_exists($pendingDir)) {
                mkdir($pendingDir, 0755, true);
                Log::info('[AGENDA FILE] Dossier pending créé');
            }

            // Créer l'entrée en base d'abord pour obtenir l'ID
            $agenda = Agenda::create([
                'title' => $this->title ?: null,
                'pdf_path' => '', // Temporaire, sera mis à jour après
                'pdf_filename' => $pdfFilename,
                'description' => $this->description ?: null,
                'start_date' => $this->startDate,
                'end_date' => $this->endDate,
                'status' => Agenda::STATUS_PENDING,
                'uploaded_by' => auth()->id(),
            ]);

            Log::info('[AGENDA] Agenda créé en BDD', [
                'agenda_id' => $agenda->id,
                'title' => $agenda->title,
                'status' => $agenda->status,
                'start_date' => $this->startDate,
                'end_date' => $this->endDate,
            ]);

            // Stocker le PDF avec l'ID de l'agenda
            $pdfPath = $this->pdfFile->storeAs('agendas/pending', $agenda->id . '.pdf', 'public');

            Log::info('[AGENDA FILE] PDF stocké dans pending', [
                'agenda_id' => $agenda->id,
                'pdf_path' => $pdfPath,
            ]);

            // Mettre à jour le chemin du PDF
            $agenda->update(['pdf_path' => $pdfPath]);

            // Notifier les super-admins du nouvel agenda programmé
            SendNewAgendaNotification::dispatch($agenda);

            Log::info('[AGENDA] Notification dispatchée', [
                'agenda_id' => $agenda->id,
                'title' => $agenda->title,
            ]);

            Log::info('[AGENDA] Upload nouvel agenda - Succès', [
                'user_id' => auth()->id(),
                'agenda_id' => $agenda->id,
                'title' => $agenda->title,
                'start_date' => $this->startDate,
            ]);

            session()->flash('success', 'Agenda ajouté en attente. Il sera activé automatiquement le ' . \Carbon\Carbon::parse($this->startDate)->format('d/m/Y') . '.');
            $this->reset(['pdfFile', 'title', 'description', 'startDate', 'endDate']);

        } catch (\Exception $e) {
            Log::error('[AGENDA] Upload nouvel agenda - Erreur', [
                'user_id' => auth()->id(),
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            session()->flash('error', 'Erreur lors de l\'upload: ' . $e->getMessage());
        }
    }

    /**
     * Ouvrir le modal d'édition
     */
    public function openEditModal($agendaId)
    {
        $this->editingAgenda = Agenda::findOrFail($agendaId);
        $this->editTitle = $this->editingAgenda->title ?? '';
        $this->editDescription = $this->editingAgenda->description ?? '';
        $this->editStartDate = $this->editingAgenda->start_date->format('Y-m-d');
        $this->editEndDate = $this->editingAgenda->end_date->format('Y-m-d');
        $this->showEditModal = true;
    }

    /**
     * Fermer le modal d'édition
     */
    public function closeEditModal()
    {
        $this->showEditModal = false;
        $this->editingAgenda = null;
        $this->reset(['editTitle', 'editDescription', 'editStartDate', 'editEndDate', 'editPdfFile']);
    }

    /**
     * Mettre à jour un agenda
     */
    public function updateAgenda()
    {
        if (!$this->editingAgenda) {
            return;
        }

        $this->authorize('update', $this->editingAgenda);

        Log::info('[AGENDA] Modification agenda - Début', [
            'user_id' => auth()->id(),
            'agenda_id' => $this->editingAgenda->id,
            'agenda_status' => $this->editingAgenda->status,
            'has_new_pdf' => (bool)$this->editPdfFile,
        ]);

        $this->validate([
            'editTitle' => 'nullable|string|max:255',
            'editDescription' => 'nullable|string|max:1000',
            'editStartDate' => 'required|date',
            'editEndDate' => 'required|date|after_or_equal:editStartDate',
            'editPdfFile' => 'nullable|mimes:pdf|max:51200',
        ]);

        // Gestion du remplacement du PDF si un nouveau fichier est fourni
        if ($this->editPdfFile) {
            // Valider le MIME type pour la sécurité
            if (!in_array($this->editPdfFile->getMimeType(), self::ALLOWED_PDF_MIME_TYPES)) {
                Log::warning('[AGENDA] Modification agenda - MIME type PDF invalide', [
                    'user_id' => auth()->id(),
                    'agenda_id' => $this->editingAgenda->id,
                    'mime_type' => $this->editPdfFile->getMimeType(),
                ]);
                session()->flash('error', 'Type de fichier PDF non autorisé.');
                return;
            }

            $oldPath = $this->editingAgenda->pdf_path;

            // Déterminer le chemin selon le statut de l'agenda
            if ($this->editingAgenda->isCurrent()) {
                // Pour l'agenda en cours : remplacer agenda-en-cours.pdf
                $newPdfPath = 'agendas/agenda-en-cours.pdf';
                if (Storage::disk('public')->exists($newPdfPath)) {
                    Storage::disk('public')->delete($newPdfPath);
                }
                $this->editPdfFile->storeAs('agendas', 'agenda-en-cours.pdf', 'public');
                $this->editingAgenda->pdf_path = $newPdfPath;
                $this->editingAgenda->pdf_filename = $this->editPdfFile->getClientOriginalName();

                Log::info('[AGENDA FILE] PDF agenda courant remplacé', [
                    'agenda_id' => $this->editingAgenda->id,
                    'new_path' => $newPdfPath,
                ]);

            } elseif ($this->editingAgenda->isPending()) {
                // Pour un agenda en attente : remplacer dans pending
                if ($oldPath && Storage::disk('public')->exists($oldPath)) {
                    Storage::disk('public')->delete($oldPath);
                }
                $this->editPdfFile->storeAs('agendas/pending', $this->editingAgenda->id . '.pdf', 'public');
                $this->editingAgenda->pdf_path = 'agendas/pending/' . $this->editingAgenda->id . '.pdf';
                $this->editingAgenda->pdf_filename = $this->editPdfFile->getClientOriginalName();

                Log::info('[AGENDA FILE] PDF agenda pending remplacé', [
                    'agenda_id' => $this->editingAgenda->id,
                    'old_path' => $oldPath,
                    'new_path' => $this->editingAgenda->pdf_path,
                ]);

            } elseif ($this->editingAgenda->isArchived()) {
                // Pour un agenda archivé : utiliser le nommage par date
                $newArchiveFilename = $this->editStartDate . '_' . $this->editEndDate . '.pdf';
                $newPdfPath = 'agendas/archives/' . $newArchiveFilename;

                if ($oldPath && $oldPath !== $newPdfPath && Storage::disk('public')->exists($oldPath)) {
                    Storage::disk('public')->delete($oldPath);
                }
                $this->editPdfFile->storeAs('agendas/archives', $newArchiveFilename, 'public');
                $this->editingAgenda->pdf_path = $newPdfPath;
                $this->editingAgenda->pdf_filename = $this->editPdfFile->getClientOriginalName();

                Log::info('[AGENDA FILE] PDF agenda archivé remplacé', [
                    'agenda_id' => $this->editingAgenda->id,
                    'old_path' => $oldPath,
                    'new_path' => $newPdfPath,
                ]);
            }
        } else {
            // Si pas de nouveau PDF mais agenda archivé avec changement de dates, renommer le fichier
            if ($this->editingAgenda->isArchived()) {
                $oldArchivePath = $this->editingAgenda->pdf_path;
                $newArchiveFilename = $this->editStartDate . '_' . $this->editEndDate . '.pdf';
                $newArchivePath = 'agendas/archives/' . $newArchiveFilename;

                if ($oldArchivePath !== $newArchivePath && Storage::disk('public')->exists($oldArchivePath)) {
                    Storage::disk('public')->move($oldArchivePath, $newArchivePath);
                    $this->editingAgenda->pdf_path = $newArchivePath;

                    Log::info('[AGENDA FILE] PDF archivé renommé (changement dates)', [
                        'agenda_id' => $this->editingAgenda->id,
                        'old_path' => $oldArchivePath,
                        'new_path' => $newArchivePath,
                    ]);
                }
            }
        }

        $this->editingAgenda->update([
            'title' => $this->editTitle ?: null,
            'description' => $this->editDescription ?: null,
            'start_date' => $this->editStartDate,
            'end_date' => $this->editEndDate,
            'pdf_path' => $this->editingAgenda->pdf_path,
            'pdf_filename' => $this->editingAgenda->pdf_filename,
        ]);

        Log::info('[AGENDA] Modification agenda - Succès', [
            'user_id' => auth()->id(),
            'agenda_id' => $this->editingAgenda->id,
            'title' => $this->editTitle,
            'start_date' => $this->editStartDate,
            'end_date' => $this->editEndDate,
        ]);

        session()->flash('success', 'Agenda mis à jour avec succès.');
        $this->closeEditModal();
    }

    /**
     * Ouvrir le modal de suppression
     */
    public function openDeleteModal($agendaId)
    {
        $this->selectedAgenda = Agenda::findOrFail($agendaId);
        $this->showDeleteModal = true;
    }

    /**
     * Fermer le modal de suppression
     */
    public function closeDeleteModal()
    {
        $this->showDeleteModal = false;
        $this->selectedAgenda = null;
    }

    /**
     * Supprimer un agenda
     */
    public function deleteAgenda($agendaId)
    {
        $agenda = Agenda::findOrFail($agendaId);
        $this->authorize('delete', $agenda);

        Log::info('[AGENDA] Suppression agenda demandée', [
            'user_id' => auth()->id(),
            'agenda_id' => $agendaId,
            'agenda_title' => $agenda->title,
            'agenda_status' => $agenda->status,
            'pdf_path' => $agenda->pdf_path,
        ]);

        // Le fichier physique sera supprimé par le model event
        $agenda->delete();

        Log::info('[AGENDA] Suppression agenda - Succès', [
            'user_id' => auth()->id(),
            'agenda_id' => $agendaId,
        ]);

        session()->flash('success', 'Agenda supprimé avec succès.');
        $this->closeDeleteModal();
    }

    public function render()
    {
        $currentAgenda = Agenda::current()->with(['uploader', 'category', 'author'])->first();
        $pendingAgenda = Agenda::pending()->with('uploader')->first();
        $archivedAgendas = Agenda::archived()
            ->with('uploader')
            ->orderBy('end_date', 'desc')
            ->paginate(10);

        return view('livewire.admin.agenda-manager', [
            'currentAgenda' => $currentAgenda,
            'pendingAgenda' => $pendingAgenda,
            'archivedAgendas' => $archivedAgendas,
            'coverImageUrl' => Agenda::getCoverImageUrl(),
            'coverThumbnailUrl' => Agenda::getCoverThumbnailUrl(),
            'hasCoverImage' => Agenda::hasCoverImage(),
            'categories' => Category::orderBy('name')->get(),
            'authors' => Author::orderBy('name')->get(),
        ])->layout('components.layouts.app');
    }
}
