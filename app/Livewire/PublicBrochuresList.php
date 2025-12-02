<?php

namespace App\Livewire;

use App\Models\BrochureReport;
use App\Models\Image;
use App\Services\MailjetService;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Rule;
use Livewire\Component;

class PublicBrochuresList extends Component
{
    public bool $showReportModal = false;
    public ?int $selectedBrochureId = null;
    public ?string $selectedBrochureTitle = null;

    #[Rule('required|string|min:10|max:1000')]
    public string $reportComment = '';

    public function openReportModal(int $brochureId): void
    {
        $brochure = Image::find($brochureId);
        if (!$brochure) {
            return;
        }

        $this->selectedBrochureId = $brochureId;
        $this->selectedBrochureTitle = $brochure->title ?? $brochure->name;
        $this->reportComment = '';
        $this->resetValidation();
        $this->showReportModal = true;
    }

    public function closeReportModal(): void
    {
        $this->showReportModal = false;
        $this->selectedBrochureId = null;
        $this->selectedBrochureTitle = null;
        $this->reportComment = '';
        $this->resetValidation();
    }

    public function submitReport(): void
    {
        if (!Auth::check()) {
            session()->flash('error', 'Vous devez être connecté pour signaler un problème.');
            $this->closeReportModal();
            return;
        }

        $this->validate();

        $report = BrochureReport::create([
            'image_id' => $this->selectedBrochureId,
            'user_id' => Auth::id(),
            'comment' => $this->reportComment,
        ]);

        // Envoyer l'email de notification
        try {
            $mailjetService = app(MailjetService::class);
            $mailjetService->sendBrochureReportNotification($report);
        } catch (\Exception $e) {
            // On log l'erreur mais on ne bloque pas l'utilisateur
            \Log::error('Failed to send brochure report email: ' . $e->getMessage());
        }

        $this->closeReportModal();
        session()->flash('success', 'Votre signalement a été envoyé. Merci pour votre contribution !');
    }

    public function render()
    {
        // Récupérer toutes les brochures disponibles
        // Tri: d'abord par display_order (nulls en dernier), puis par titre
        $brochures = Image::where('print_available', true)
            ->where('quantity_available', '>', 0)
            ->orderByRaw('display_order IS NULL, display_order ASC')
            ->orderBy('title')
            ->get();

        return view('livewire.public-brochures-list', [
            'brochures' => $brochures,
        ])->layout('components.layouts.guest');
    }
}
