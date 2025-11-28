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
