<?php

namespace App\Livewire\Sftp;

use App\Models\SftpUpload;
use Livewire\Component;
use Livewire\WithPagination;
use Livewire\Attributes\On;

class History extends Component
{
    use WithPagination;

    public string $search = '';
    public string $statusFilter = 'all';

    protected $queryString = [
        'search' => ['except' => ''],
        'statusFilter' => ['except' => 'all'],
    ];

    public function updatingSearch(): void
    {
        $this->resetPage();
    }

    public function updatingStatusFilter(): void
    {
        $this->resetPage();
    }

    #[On('upload-completed')]
    public function refresh(): void
    {
        // Just trigger a re-render
    }

    public function render()
    {
        $query = SftpUpload::query()
            ->with(['user', 'sftpConfiguration'])
            ->orderBy('created_at', 'desc');

        // Filter by search (filename or username)
        if (!empty($this->search)) {
            $query->where(function ($q) {
                $q->where('original_filename', 'like', '%' . $this->search . '%')
                    ->orWhere('remote_filename', 'like', '%' . $this->search . '%')
                    ->orWhereHas('user', function ($userQuery) {
                        $userQuery->where('name', 'like', '%' . $this->search . '%');
                    });
            });
        }

        // Filter by status
        if ($this->statusFilter !== 'all') {
            $query->where('status', $this->statusFilter);
        }

        $uploads = $query->paginate(15);

        return view('livewire.sftp.history', [
            'uploads' => $uploads,
        ]);
    }
}
