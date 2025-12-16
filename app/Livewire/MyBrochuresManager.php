<?php

namespace App\Livewire;

use App\Models\BrochureReport;
use App\Models\Image;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Livewire\Component;
use Livewire\Features\SupportFileUploads\WithFileUploads;
use Livewire\WithPagination;

class MyBrochuresManager extends Component
{
    use WithFileUploads, WithPagination;

    public $search = '';

    // Edit PDF modal properties
    public $showEditModal = false;
    public $editingImage = null;
    public $editPdfFile = null;
    public $removePdf = false;

    // Report modal properties
    public bool $showReportModal = false;
    public ?BrochureReport $selectedReport = null;
    public string $resolutionNote = '';

    // Whitelist des MIME types PDF autorisés
    private const ALLOWED_PDF_MIME_TYPES = [
        'application/pdf',
    ];

    protected $queryString = [
        'search' => ['except' => ''],
    ];

    protected $messages = [
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
        $this->reset(['editPdfFile', 'removePdf']);
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
     * Update the PDF only
     */
    public function updatePdf()
    {
        if (!$this->editingImage) {
            return;
        }

        // Verify user is still responsable of this image
        if ($this->editingImage->responsable_id !== Auth::id()) {
            session()->flash('error', 'Vous n\'êtes pas autorisé à modifier cette brochure.');
            $this->closeEditModal();
            return;
        }

        $this->validate([
            'editPdfFile' => 'nullable|mimes:pdf|max:51200',
        ]);

        // Handle PDF update
        $pdfPath = $this->editingImage->pdf_path;

        // Remove PDF if requested
        if ($this->removePdf && $pdfPath) {
            if (Storage::disk('public')->exists($pdfPath)) {
                Storage::disk('public')->delete($pdfPath);
            }
            $pdfPath = null;
        }

        // Upload new PDF if provided
        if ($this->editPdfFile && !$this->removePdf) {
            // Validate MIME type
            if (!in_array($this->editPdfFile->getMimeType(), self::ALLOWED_PDF_MIME_TYPES)) {
                session()->flash('error', 'Type de fichier PDF non autorisé.');
                return;
            }

            // If a PDF already exists, overwrite with the same name (stable URL)
            if ($this->editingImage->pdf_path) {
                $pdfFilename = basename($this->editingImage->pdf_path);
                Storage::disk('public')->putFileAs('pdfs', $this->editPdfFile, $pdfFilename);
                $pdfPath = $this->editingImage->pdf_path;
            } else {
                // New PDF: use the title (fallback to image name)
                $imageSlug = Str::limit(Str::slug($this->editingImage->title) ?: Str::slug(pathinfo($this->editingImage->name, PATHINFO_FILENAME)), 100);
                if (empty($imageSlug)) {
                    $imageSlug = 'document';
                }

                // If a file already exists with this name, add a numeric suffix
                $basePdfFilename = $imageSlug . '.pdf';
                $pdfFilename = $basePdfFilename;
                $pdfCounter = 1;
                while (Storage::disk('public')->exists('pdfs/' . $pdfFilename)) {
                    $pdfFilename = $imageSlug . '-' . $pdfCounter . '.pdf';
                    $pdfCounter++;
                }
                $pdfPath = $this->editPdfFile->storeAs('pdfs', $pdfFilename, 'public');
            }
        }

        // Update only the pdf_path field
        $this->editingImage->update([
            'pdf_path' => $pdfPath,
        ]);

        // Regenerate JSON file
        Artisan::call('images:generate-json');

        session()->flash('success', 'Le PDF de la brochure a été mis à jour avec succès.');
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
