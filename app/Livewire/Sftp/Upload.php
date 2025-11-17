<?php

namespace App\Livewire\Sftp;

use App\Models\SftpConfiguration;
use App\Services\SftpService;
use Livewire\Component;
use Livewire\WithFileUploads;
use Flux\Flux;

class Upload extends Component
{
    use WithFileUploads;

    public $pdfFile;
    public bool $uploading = false;
    public ?SftpConfiguration $activeConfig = null;

    public function mount(): void
    {
        $this->activeConfig = SftpConfiguration::getActive();
    }

    public function updatedPdfFile(): void
    {
        $this->validate([
            'pdfFile' => 'required|file|mimes:pdf|max:51200', // Max 50MB
        ], [
            'pdfFile.required' => 'Veuillez sélectionner un fichier.',
            'pdfFile.file' => 'Le fichier téléchargé n\'est pas valide.',
            'pdfFile.mimes' => 'Seuls les fichiers PDF sont acceptés.',
            'pdfFile.max' => 'Le fichier ne doit pas dépasser 50 Mo.',
        ]);
    }

    public function upload(): void
    {
        // Validate file
        $this->validate([
            'pdfFile' => 'required|file|mimes:pdf|max:51200',
        ], [
            'pdfFile.required' => 'Veuillez sélectionner un fichier PDF.',
            'pdfFile.file' => 'Le fichier téléchargé n\'est pas valide.',
            'pdfFile.mimes' => 'Seuls les fichiers PDF sont acceptés.',
            'pdfFile.max' => 'Le fichier ne doit pas dépasser 50 Mo.',
        ]);

        // Check if configuration exists
        if (!$this->activeConfig) {
            Flux::toast('Aucune configuration SFTP active. Veuillez configurer le serveur SFTP d\'abord.', variant: 'danger');
            return;
        }

        $this->uploading = true;

        try {
            $sftpService = app(SftpService::class);
            $result = $sftpService->uploadFile($this->pdfFile, auth()->id());

            if ($result['success']) {
                Flux::toast($result['message'], variant: 'success');

                // Reset file input
                $this->reset('pdfFile');

                // Dispatch event to refresh history if present on page
                $this->dispatch('upload-completed');
            } else {
                Flux::toast($result['message'], variant: 'danger');
            }
        } catch (\Exception $e) {
            Flux::toast('Erreur lors de l\'upload: ' . $e->getMessage(), variant: 'danger');
        } finally {
            $this->uploading = false;
        }
    }

    public function clearFile(): void
    {
        $this->reset('pdfFile');
    }

    public function render()
    {
        return view('livewire.sftp.upload');
    }
}
