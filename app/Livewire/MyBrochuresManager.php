<?php

namespace App\Livewire;

use App\Models\BrochureReport;
use App\Models\Image;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Intervention\Image\Drivers\Gd\Driver;
use Intervention\Image\ImageManager as InterventionImageManager;
use Livewire\Component;
use Livewire\Features\SupportFileUploads\WithFileUploads;
use Livewire\WithPagination;

class MyBrochuresManager extends Component
{
    use WithFileUploads, WithPagination;

    public $search = '';

    // Edit modal properties
    public $showEditModal = false;
    public $editingImage = null;
    public $editTitle = '';
    public $editDescription = '';
    public $editPresentationImage = null;
    public $editPdfFile = null;
    public $removePdf = false;

    // Report modal properties
    public bool $showReportModal = false;
    public ?BrochureReport $selectedReport = null;
    public string $resolutionNote = '';

    // Whitelist des MIME types autorisés
    private const ALLOWED_PDF_MIME_TYPES = [
        'application/pdf',
    ];

    private const ALLOWED_IMAGE_MIME_TYPES = [
        'image/jpeg',
        'image/png',
        'image/gif',
        'image/webp',
    ];

    protected $queryString = [
        'search' => ['except' => ''],
    ];

    protected $messages = [
        'editTitle.max' => 'Le titre ne doit pas dépasser 255 caractères.',
        'editDescription.max' => 'La description ne doit pas dépasser 1000 caractères.',
        'editPresentationImage.image' => 'Le fichier doit être une image.',
        'editPresentationImage.max' => 'L\'image ne doit pas dépasser 10 MB.',
        'editPdfFile.mimes' => 'Le fichier doit être un PDF.',
        'editPdfFile.max' => 'Le PDF ne doit pas dépasser 50 MB.',
    ];

    public function updatingSearch()
    {
        $this->resetPage();
    }

    /**
     * Open the PDF edit modal
     */
    public function openEditModal($imageId)
    {
        $image = Image::where('responsable_id', Auth::id())
            ->findOrFail($imageId);

        $this->editingImage = $image;
        $this->editTitle = $image->title ?? '';
        $this->editDescription = $image->description ?? '';
        $this->editPresentationImage = null;
        $this->editPdfFile = null;
        $this->removePdf = false;
        $this->showEditModal = true;
    }

    /**
     * Close the edit modal
     */
    public function closeEditModal()
    {
        $this->showEditModal = false;
        $this->editingImage = null;
        $this->reset(['editTitle', 'editDescription', 'editPresentationImage', 'editPdfFile', 'removePdf']);
    }

    /**
     * Open the report detail modal
     */
    public function openReportModal(int $reportId): void
    {
        // Load report with verification that the image belongs to the responsable
        $this->selectedReport = BrochureReport::with(['image', 'user'])
            ->whereHas('image', fn($q) => $q->where('responsable_id', Auth::id()))
            ->find($reportId);

        if ($this->selectedReport) {
            // Mark as read
            if (!$this->selectedReport->is_read) {
                $this->selectedReport->markAsRead();
            }
            $this->resolutionNote = '';
            $this->showReportModal = true;
        }
    }

    /**
     * Close the report modal
     */
    public function closeReportModal(): void
    {
        $this->showReportModal = false;
        $this->selectedReport = null;
        $this->resolutionNote = '';
    }

    /**
     * Resolve a report
     */
    public function resolveReport(): void
    {
        if (!$this->selectedReport) {
            return;
        }

        // Verify the report concerns a brochure the user is responsable for
        if ($this->selectedReport->image->responsable_id !== Auth::id()) {
            session()->flash('error', 'Vous n\'êtes pas autorisé à résoudre ce signalement.');
            $this->closeReportModal();
            return;
        }

        $this->selectedReport->resolve(Auth::user(), $this->resolutionNote ?: null);
        session()->flash('success', 'Le signalement a été marqué comme résolu.');
        $this->closeReportModal();
    }

    /**
     * Update the brochure (title, description, presentation image, PDF)
     */
    public function updateBrochure()
    {
        if (!$this->editingImage) {
            return;
        }

        $userId = Auth::id();
        $imageId = $this->editingImage->id;

        Log::info('Brochure update initiated', [
            'user_id' => $userId,
            'image_id' => $imageId,
            'has_new_image' => (bool) $this->editPresentationImage,
            'has_new_pdf' => (bool) $this->editPdfFile,
            'remove_pdf_requested' => $this->removePdf,
        ]);

        // Verify user is still responsable of this image
        if ($this->editingImage->responsable_id !== $userId) {
            Log::warning('Brochure update unauthorized', [
                'user_id' => $userId,
                'image_id' => $imageId,
                'responsable_id' => $this->editingImage->responsable_id,
            ]);
            session()->flash('error', 'Vous n\'êtes pas autorisé à modifier cette brochure.');
            $this->closeEditModal();
            return;
        }

        $this->validate([
            'editTitle' => 'nullable|max:255',
            'editDescription' => 'nullable|max:1000',
            'editPresentationImage' => 'nullable|image|max:10240',
            'editPdfFile' => 'nullable|mimes:pdf|max:51200',
        ]);

        $updateData = [
            'title' => $this->editTitle ?: null,
            'description' => $this->editDescription ?: null,
        ];

        // === Handle presentation image ===
        if ($this->editPresentationImage) {
            if (!in_array($this->editPresentationImage->getMimeType(), self::ALLOWED_IMAGE_MIME_TYPES)) {
                Log::warning('Image upload rejected - invalid MIME type', [
                    'user_id' => $userId,
                    'image_id' => $imageId,
                    'mime_type' => $this->editPresentationImage->getMimeType(),
                ]);
                session()->flash('error', 'Type de fichier image non autorisé.');
                return;
            }

            $imageSlug = Str::limit(Str::slug($this->editTitle ?: $this->editingImage->name), 100) ?: 'brochure';
            $extension = strtolower($this->editPresentationImage->getClientOriginalExtension());
            $presentationFilename = $imageSlug . '.' . $extension;
            $counter = 1;
            while (Storage::disk('public')->exists('images/' . $presentationFilename)) {
                $presentationFilename = $imageSlug . '-' . $counter . '.' . $extension;
                $counter++;
            }

            $newPath = $this->editPresentationImage->storeAs('images', $presentationFilename, 'public');
            $fullPath = Storage::disk('public')->path($newPath);

            // Process image with Intervention
            $manager = new InterventionImageManager(new Driver());
            $img = $manager->read($fullPath);
            $updateData['width'] = $img->width();
            $updateData['height'] = $img->height();
            $img->save($fullPath, quality: 85);
            $updateData['mime_type'] = $this->editPresentationImage->getMimeType();

            // Generate thumbnail
            $thumbnailFilename = 'thumb_' . $presentationFilename;
            $thumbnailPath = 'images/thumbnails/' . $thumbnailFilename;
            $thumbnailFullPath = Storage::disk('public')->path($thumbnailPath);
            $thumbnailDir = dirname($thumbnailFullPath);
            if (!file_exists($thumbnailDir)) {
                mkdir($thumbnailDir, 0755, true);
            }
            $thumbnail = $manager->read($fullPath);
            $thumbnail->cover(300, 300);
            $thumbnail->save($thumbnailFullPath, quality: 80);

            // Delete old files
            if ($this->editingImage->path && Storage::disk('public')->exists($this->editingImage->path)) {
                Storage::disk('public')->delete($this->editingImage->path);
            }
            if ($this->editingImage->thumbnail_path && Storage::disk('public')->exists($this->editingImage->thumbnail_path)) {
                Storage::disk('public')->delete($this->editingImage->thumbnail_path);
            }

            $updateData['path'] = $newPath;
            $updateData['thumbnail_path'] = $thumbnailPath;

            Log::info('Presentation image updated', [
                'user_id' => $userId,
                'image_id' => $imageId,
                'new_path' => $newPath,
            ]);
        }

        // === Handle PDF ===
        $pdfPath = $this->editingImage->pdf_path;

        // Remove PDF if requested
        if ($this->removePdf && $pdfPath) {
            Log::info('PDF removal requested', [
                'user_id' => $userId,
                'image_id' => $imageId,
                'deleted_pdf_path' => $pdfPath,
            ]);
            if (Storage::disk('public')->exists($pdfPath)) {
                Storage::disk('public')->delete($pdfPath);
            }
            $pdfPath = null;
        }

        // Upload new PDF if provided
        if ($this->editPdfFile && !$this->removePdf) {
            if (!in_array($this->editPdfFile->getMimeType(), self::ALLOWED_PDF_MIME_TYPES)) {
                Log::warning('PDF upload rejected - invalid MIME type', [
                    'user_id' => $userId,
                    'image_id' => $imageId,
                    'mime_type' => $this->editPdfFile->getMimeType(),
                ]);
                session()->flash('error', 'Type de fichier PDF non autorisé.');
                return;
            }

            if ($this->editingImage->pdf_path) {
                $pdfFilename = basename($this->editingImage->pdf_path);
                Storage::disk('public')->putFileAs('pdfs', $this->editPdfFile, $pdfFilename);
                $pdfPath = $this->editingImage->pdf_path;
                Log::info('PDF replaced (overwrite)', [
                    'user_id' => $userId,
                    'image_id' => $imageId,
                    'pdf_filename' => $pdfFilename,
                    'file_size_kb' => round($this->editPdfFile->getSize() / 1024, 2),
                ]);
            } else {
                $imageSlug = Str::limit(Str::slug($this->editTitle ?: $this->editingImage->name), 100) ?: 'document';
                $basePdfFilename = $imageSlug . '.pdf';
                $pdfFilename = $basePdfFilename;
                $pdfCounter = 1;
                while (Storage::disk('public')->exists('pdfs/' . $pdfFilename)) {
                    $pdfFilename = $imageSlug . '-' . $pdfCounter . '.pdf';
                    $pdfCounter++;
                }
                $pdfPath = $this->editPdfFile->storeAs('pdfs', $pdfFilename, 'public');
                Log::info('New PDF added', [
                    'user_id' => $userId,
                    'image_id' => $imageId,
                    'new_pdf_path' => $pdfPath,
                    'file_size_kb' => round($this->editPdfFile->getSize() / 1024, 2),
                ]);
            }
        }

        $updateData['pdf_path'] = $pdfPath;

        // Update the brochure
        $this->editingImage->update($updateData);

        // Regenerate JSON file
        Artisan::call('images:generate-json');

        Log::info('Brochure update completed', [
            'user_id' => $userId,
            'image_id' => $imageId,
            'brochure_title' => $this->editTitle,
        ]);

        session()->flash('success', 'La brochure a été mise à jour avec succès.');
        $this->closeEditModal();
    }

    public function render()
    {
        $query = Image::with(['category', 'author', 'sector'])
            ->where('responsable_id', Auth::id())
            ->orderByRaw('display_order IS NULL, display_order ASC')
            ->orderBy('created_at', 'desc');

        // Search
        if ($this->search) {
            $query->where(function ($q) {
                $q->where('name', 'like', '%' . $this->search . '%')
                    ->orWhere('title', 'like', '%' . $this->search . '%')
                    ->orWhere('filename', 'like', '%' . $this->search . '%');
            });
        }

        $brochures = $query->paginate(12);

        // Get pending reports for brochures the user is responsable for
        $pendingReports = BrochureReport::with(['image', 'user'])
            ->whereHas('image', fn($q) => $q->where('responsable_id', Auth::id()))
            ->unresolved()
            ->orderBy('created_at', 'desc')
            ->get();

        $unreadReportsCount = BrochureReport::whereHas('image', fn($q) => $q->where('responsable_id', Auth::id()))
            ->unread()
            ->unresolved()
            ->count();

        return view('livewire.my-brochures-manager', [
            'brochures' => $brochures,
            'pendingReports' => $pendingReports,
            'unreadReportsCount' => $unreadReportsCount,
        ])->layout('components.layouts.app');
    }
}
