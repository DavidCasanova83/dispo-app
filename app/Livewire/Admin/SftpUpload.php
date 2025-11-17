<?php

namespace App\Livewire\Admin;

use App\Jobs\ProcessSftpUpload;
use App\Models\SftpConfiguration;
use App\Models\SftpUpload as SftpUploadModel;
use App\Services\SftpService;
use Livewire\Attributes\Validate;
use Livewire\Component;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;
use Livewire\WithFileUploads;
use Livewire\WithPagination;

class SftpUpload extends Component
{
    use WithFileUploads, WithPagination;

    #[Validate('required|exists:sftp_configurations,id')]
    public $configuration_id = '';

    #[Validate('required|file|mimes:pdf|max:10240')]
    public $file;

    // UI state
    public $search = '';
    public $filterStatus = 'all';
    public $uploading = false;

    protected $queryString = [
        'search' => ['except' => ''],
        'filterStatus' => ['except' => 'all'],
        'page' => ['except' => 1],
    ];

    /**
     * Upload PDF file
     */
    public function uploadFile()
    {
        $this->validate();

        try {
            $this->uploading = true;

            // Get configuration
            $configuration = SftpConfiguration::findOrFail($this->configuration_id);

            if (!$configuration->active) {
                session()->flash('error', 'La configuration SFTP sélectionnée est inactive.');
                $this->uploading = false;
                return;
            }

            // Validate file is PDF
            $sftpService = app(SftpService::class);

            // Get the temporary file path
            $tempPath = $this->file->getRealPath();

            // Validate PDF
            $validation = $sftpService->validatePdfFile($tempPath);

            if (!$validation['valid']) {
                session()->flash('error', $validation['message']);
                $this->uploading = false;
                return;
            }

            // Generate unique filename
            $originalFilename = $this->file->getClientOriginalName();
            $storedFilename = $sftpService->generateUniqueFilename($originalFilename);

            // Create upload directory if it doesn't exist
            $uploadDir = config('sftp.local_storage_path');
            if (!is_dir($uploadDir)) {
                mkdir($uploadDir, 0755, true);
            }

            // Store file locally temporarily
            $localPath = $uploadDir . '/' . $storedFilename;
            move_uploaded_file($tempPath, $localPath);

            // Create upload record
            $upload = SftpUploadModel::create([
                'sftp_configuration_id' => $this->configuration_id,
                'user_id' => auth()->id(),
                'original_filename' => $originalFilename,
                'stored_filename' => $storedFilename,
                'local_path' => $localPath,
                'remote_path' => rtrim($configuration->remote_path, '/') . '/' . $storedFilename,
                'file_size' => $validation['size'],
                'status' => 'pending',
            ]);

            // Dispatch job to process upload
            ProcessSftpUpload::dispatch($upload);

            session()->flash('message', 'Fichier ajouté à la file d\'upload avec succès.');

            // Reset form
            $this->reset(['file', 'configuration_id']);
            $this->uploading = false;
            $this->resetPage();

        } catch (\Exception $e) {
            session()->flash('error', 'Erreur lors de l\'upload : ' . $e->getMessage());
            $this->uploading = false;
        }
    }

    /**
     * Retry failed upload
     */
    public function retry($id)
    {
        try {
            $upload = SftpUploadModel::findOrFail($id);

            if (!$upload->isFailed()) {
                session()->flash('error', 'Seuls les uploads échoués peuvent être réessayés.');
                return;
            }

            // Check if local file still exists
            if (!file_exists($upload->local_path)) {
                session()->flash('error', 'Le fichier local n\'existe plus. Veuillez uploader à nouveau.');
                return;
            }

            // Reset status
            $upload->update([
                'status' => 'pending',
                'error_message' => null,
            ]);

            // Dispatch job again
            ProcessSftpUpload::dispatch($upload);

            session()->flash('message', 'Upload réessayé avec succès.');

        } catch (\Exception $e) {
            session()->flash('error', 'Erreur lors du retry : ' . $e->getMessage());
        }
    }

    /**
     * Delete upload record
     */
    public function delete($id)
    {
        try {
            $upload = SftpUploadModel::findOrFail($id);

            // Delete local file if exists
            if (file_exists($upload->local_path)) {
                unlink($upload->local_path);
            }

            $upload->delete();

            session()->flash('message', 'Upload supprimé avec succès.');
            $this->resetPage();

        } catch (\Exception $e) {
            session()->flash('error', 'Erreur lors de la suppression : ' . $e->getMessage());
        }
    }

    /**
     * Render component
     */
    public function render()
    {
        $configurations = SftpConfiguration::where('active', true)
            ->orderBy('name')
            ->get();

        $uploads = SftpUploadModel::with(['configuration', 'user'])
            ->when($this->search, function ($query) {
                $query->where('original_filename', 'like', '%' . $this->search . '%')
                    ->orWhere('stored_filename', 'like', '%' . $this->search . '%');
            })
            ->when($this->filterStatus !== 'all', function ($query) {
                $query->where('status', $this->filterStatus);
            })
            ->latest()
            ->paginate(15);

        // Statistics
        $stats = [
            'total' => SftpUploadModel::count(),
            'pending' => SftpUploadModel::where('status', 'pending')->count(),
            'uploading' => SftpUploadModel::where('status', 'uploading')->count(),
            'completed' => SftpUploadModel::where('status', 'completed')->count(),
            'failed' => SftpUploadModel::where('status', 'failed')->count(),
        ];

        return view('livewire.admin.sftp-upload', [
            'configurations' => $configurations,
            'uploads' => $uploads,
            'stats' => $stats,
        ])->layout('components.layouts.app');
    }
}
