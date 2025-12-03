<?php

namespace App\Livewire\Admin;

use App\Models\Agenda;
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
     * Upload de l'image de couverture globale
     */
    public function uploadCoverImage()
    {
        // Rate limiting
        $executed = RateLimiter::attempt(
            'upload-cover:' . auth()->id(),
            5,
            function () {},
            60
        );

        if (!$executed) {
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
                session()->flash('error', 'Type d\'image non autorisé.');
                return;
            }

            // Valider extension
            $allowedExtensions = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
            $extension = strtolower($this->coverImage->getClientOriginalExtension());
            if (!in_array($extension, $allowedExtensions)) {
                session()->flash('error', 'Extension d\'image non autorisée.');
                return;
            }

            // S'assurer que le dossier existe
            $agendaDir = Storage::disk('public')->path('agendas');
            if (!file_exists($agendaDir)) {
                mkdir($agendaDir, 0755, true);
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

            // Créer le thumbnail
            $thumbnailFullPath = Storage::disk('public')->path(Agenda::COVER_THUMBNAIL_PATH);
            $thumbnail = $manager->read($tempFullPath);
            $thumbnail->cover(300, 300);
            $thumbnail->save($thumbnailFullPath, quality: 80);

            // Supprimer le fichier temporaire
            Storage::disk('public')->delete($tempPath);

            session()->flash('success', 'Image de couverture mise à jour avec succès.');
            $this->reset(['coverImage']);

        } catch (\Exception $e) {
            session()->flash('error', 'Erreur lors de l\'upload: ' . $e->getMessage());
        }
    }

    /**
     * Upload d'un nouvel agenda (PDF uniquement)
     */
    public function uploadAgenda()
    {
        // Rate limiting
        $executed = RateLimiter::attempt(
            'upload-agenda:' . auth()->id(),
            5,
            function () {},
            60
        );

        if (!$executed) {
            session()->flash('error', 'Trop de tentatives d\'upload. Veuillez attendre avant de réessayer.');
            return;
        }

        $this->authorize('create', Agenda::class);

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
                session()->flash('error', 'Type de fichier PDF non autorisé.');
                return;
            }

            // Archiver l'agenda courant s'il existe
            $this->archiveCurrentAgenda();

            // Sauvegarder le PDF comme agenda-en-cours.pdf
            $pdfFilename = $this->pdfFile->getClientOriginalName();

            // S'assurer que le dossier existe
            $agendaDir = Storage::disk('public')->path('agendas');
            if (!file_exists($agendaDir)) {
                mkdir($agendaDir, 0755, true);
            }

            $pdfPath = $this->pdfFile->storeAs('agendas', 'agenda-en-cours.pdf', 'public');

            // Créer l'entrée en base
            Agenda::create([
                'title' => $this->title ?: null,
                'pdf_path' => $pdfPath,
                'pdf_filename' => $pdfFilename,
                'description' => $this->description ?: null,
                'start_date' => $this->startDate,
                'end_date' => $this->endDate,
                'is_current' => true,
                'uploaded_by' => auth()->id(),
            ]);

            session()->flash('success', 'Agenda uploadé avec succès.');
            $this->reset(['pdfFile', 'title', 'description', 'startDate', 'endDate']);

        } catch (\Exception $e) {
            session()->flash('error', 'Erreur lors de l\'upload: ' . $e->getMessage());
        }
    }

    /**
     * Archiver l'agenda courant
     */
    private function archiveCurrentAgenda(): void
    {
        $currentAgenda = Agenda::current()->first();

        if ($currentAgenda) {
            // Renommer le PDF avec les dates
            $archiveFilename = $currentAgenda->start_date->format('Y-m-d') . '_' . $currentAgenda->end_date->format('Y-m-d') . '.pdf';
            $archivePath = 'agendas/archives/' . $archiveFilename;

            // S'assurer que le dossier archives existe
            $archiveDir = Storage::disk('public')->path('agendas/archives');
            if (!file_exists($archiveDir)) {
                mkdir($archiveDir, 0755, true);
            }

            // Copier le PDF actuel vers les archives
            if (Storage::disk('public')->exists('agendas/agenda-en-cours.pdf')) {
                Storage::disk('public')->copy('agendas/agenda-en-cours.pdf', $archivePath);
            }

            // Mettre à jour l'agenda en base
            $currentAgenda->update([
                'is_current' => false,
                'archived_at' => now(),
                'pdf_path' => $archivePath,
            ]);
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
        $this->reset(['editTitle', 'editDescription', 'editStartDate', 'editEndDate']);
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

        $this->validate([
            'editTitle' => 'nullable|string|max:255',
            'editDescription' => 'nullable|string|max:1000',
            'editStartDate' => 'required|date',
            'editEndDate' => 'required|date|after_or_equal:editStartDate',
        ]);

        // Si c'est un agenda archivé et que les dates changent, renommer le PDF
        if (!$this->editingAgenda->is_current) {
            $oldArchivePath = $this->editingAgenda->pdf_path;
            $newArchiveFilename = $this->editStartDate . '_' . $this->editEndDate . '.pdf';
            $newArchivePath = 'agendas/archives/' . $newArchiveFilename;

            if ($oldArchivePath !== $newArchivePath && Storage::disk('public')->exists($oldArchivePath)) {
                Storage::disk('public')->move($oldArchivePath, $newArchivePath);
                $this->editingAgenda->pdf_path = $newArchivePath;
            }
        }

        $this->editingAgenda->update([
            'title' => $this->editTitle ?: null,
            'description' => $this->editDescription ?: null,
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

        // Le fichier physique sera supprimé par le model event
        $agenda->delete();

        session()->flash('success', 'Agenda supprimé avec succès.');
        $this->closeDeleteModal();
    }

    public function render()
    {
        $currentAgenda = Agenda::current()->with('uploader')->first();
        $archivedAgendas = Agenda::archived()
            ->with('uploader')
            ->orderBy('end_date', 'desc')
            ->paginate(10);

        return view('livewire.admin.agenda-manager', [
            'currentAgenda' => $currentAgenda,
            'archivedAgendas' => $archivedAgendas,
            'coverImageUrl' => Agenda::getCoverImageUrl(),
            'coverThumbnailUrl' => Agenda::getCoverThumbnailUrl(),
            'hasCoverImage' => Agenda::hasCoverImage(),
        ])->layout('components.layouts.app');
    }
}
