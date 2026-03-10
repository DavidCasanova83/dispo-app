<?php

namespace App\Livewire\Admin;

use App\Models\ContactFormSubmission;
use Livewire\Component;
use Livewire\WithPagination;

class ContactFormSubmissions extends Component
{
    use WithPagination;

    public $search = '';
    public $filterStatus = 'all'; // all, read, unread
    public $selectedSubmission = null;
    public $showDetailModal = false;

    protected $queryString = [
        'search' => ['except' => ''],
        'filterStatus' => ['except' => 'all'],
    ];

    public function updatingSearch()
    {
        $this->resetPage();
    }

    public function updatingFilterStatus()
    {
        $this->resetPage();
    }

    public function openDetailModal($submissionId)
    {
        $this->selectedSubmission = ContactFormSubmission::findOrFail($submissionId);

        // Mark as read when opening
        if (!$this->selectedSubmission->is_read) {
            $this->selectedSubmission->markAsRead();
        }

        $this->showDetailModal = true;
    }

    public function closeDetailModal()
    {
        $this->showDetailModal = false;
        $this->selectedSubmission = null;
    }

    public function markAsUnread($submissionId)
    {
        $submission = ContactFormSubmission::findOrFail($submissionId);
        $submission->markAsUnread();
        $this->closeDetailModal();
    }

    public function deleteSubmission($submissionId)
    {
        ContactFormSubmission::findOrFail($submissionId)->delete();
        $this->closeDetailModal();
        session()->flash('success', 'Soumission supprimée avec succès.');
    }

    public function exportCsv()
    {
        $query = ContactFormSubmission::query();

        if ($this->search) {
            $query->where(function ($q) {
                $q->where('visiteur_nom', 'like', '%' . $this->search . '%')
                  ->orWhere('visiteur_prenom', 'like', '%' . $this->search . '%')
                  ->orWhere('visiteur_email', 'like', '%' . $this->search . '%')
                  ->orWhere('etablissement_nom', 'like', '%' . $this->search . '%')
                  ->orWhere('etablissement_apidae_id', 'like', '%' . $this->search . '%');
            });
        }

        if ($this->filterStatus === 'read') {
            $query->read();
        } elseif ($this->filterStatus === 'unread') {
            $query->unread();
        }

        $submissions = $query->latest()->get();

        $filename = 'soumissions_contact_' . date('Y-m-d_His') . '.csv';
        $handle = fopen('php://temp', 'w');

        // BOM UTF-8 pour compatibilité Excel
        fprintf($handle, chr(0xEF) . chr(0xBB) . chr(0xBF));

        fputcsv($handle, [
            'Date de soumission',
            'Statut',
            'Nom',
            'Prénom',
            'Email visiteur',
            'Téléphone',
            'Message',
            'ID Apidae établissement',
            'Nom établissement',
            'Email établissement',
            'URL page',
            'IP visiteur',
            'Form ID',
        ]);

        foreach ($submissions as $submission) {
            fputcsv($handle, [
                $submission->date_soumission?->format('d/m/Y H:i') ?? $submission->created_at->format('d/m/Y H:i'),
                $submission->is_read ? 'Lu' : 'Non lu',
                $submission->visiteur_nom,
                $submission->visiteur_prenom ?? '',
                $submission->visiteur_email,
                $submission->visiteur_telephone ?? '',
                $submission->visiteur_message ?? '',
                $submission->etablissement_apidae_id ?? '',
                $submission->etablissement_nom ?? '',
                $submission->etablissement_email ?? '',
                $submission->url_page ?? '',
                $submission->ip_visiteur ?? '',
                $submission->form_id,
            ]);
        }

        rewind($handle);
        $csv = stream_get_contents($handle);
        fclose($handle);

        return response()->streamDownload(function () use ($csv) {
            echo $csv;
        }, $filename, ['Content-Type' => 'text/csv']);
    }

    public function render()
    {
        $query = ContactFormSubmission::query();

        // Search filter
        if ($this->search) {
            $query->where(function ($q) {
                $q->where('visiteur_nom', 'like', '%' . $this->search . '%')
                  ->orWhere('visiteur_prenom', 'like', '%' . $this->search . '%')
                  ->orWhere('visiteur_email', 'like', '%' . $this->search . '%')
                  ->orWhere('etablissement_nom', 'like', '%' . $this->search . '%')
                  ->orWhere('etablissement_apidae_id', 'like', '%' . $this->search . '%');
            });
        }

        // Status filter
        if ($this->filterStatus === 'read') {
            $query->read();
        } elseif ($this->filterStatus === 'unread') {
            $query->unread();
        }

        $submissions = $query->latest()->paginate(15);

        $stats = [
            'total' => ContactFormSubmission::count(),
            'unread' => ContactFormSubmission::unread()->count(),
            'today' => ContactFormSubmission::whereDate('created_at', today())->count(),
            'this_week' => ContactFormSubmission::where('created_at', '>=', now()->startOfWeek())->count(),
        ];

        return view('livewire.admin.contact-form-submissions', [
            'submissions' => $submissions,
            'stats' => $stats,
        ])->layout('components.layouts.app');
    }
}
