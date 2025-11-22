<?php

namespace App\Livewire\Admin;

use App\Models\ImageOrder;
use Livewire\Component;
use Livewire\WithPagination;

class OrderManager extends Component
{
    use WithPagination;

    public $search = '';
    public $statusFilter = 'all';
    public $showDetailModal = false;
    public $selectedOrder = null;
    public $adminNotes = '';

    protected $queryString = [
        'search' => ['except' => ''],
        'statusFilter' => ['except' => 'all'],
    ];

    public function updatingSearch()
    {
        $this->resetPage();
    }

    public function updatingStatusFilter()
    {
        $this->resetPage();
    }

    public function openDetailModal($orderId)
    {
        $this->selectedOrder = ImageOrder::with(['items.image'])->findOrFail($orderId);
        $this->adminNotes = $this->selectedOrder->admin_notes ?? '';
        $this->showDetailModal = true;
    }

    public function closeDetailModal()
    {
        $this->showDetailModal = false;
        $this->selectedOrder = null;
        $this->adminNotes = '';
    }

    public function updateStatus($orderId, $newStatus)
    {
        $order = ImageOrder::findOrFail($orderId);
        $order->update(['status' => $newStatus]);
        
        session()->flash('success', 'Statut mis à jour avec succès.');
    }

    public function saveAdminNotes()
    {
        if ($this->selectedOrder) {
            $this->selectedOrder->update(['admin_notes' => $this->adminNotes]);
            session()->flash('success', 'Notes enregistrées.');
        }
    }

    public function exportCsv()
    {
        $orders = ImageOrder::with(['items.image'])
            ->search($this->search)
            ->byStatus($this->statusFilter)
            ->oldest()
            ->get();

        $filename = 'commandes_' . date('Y-m-d_His') . '.csv';
        $handle = fopen('php://temp', 'w');
        
        // Headers
        fputcsv($handle, [
            'Numéro',
            'Date',
            'Type Client',
            'Nom',
            'Prénom',
            'Société',
            'Email',
            'Téléphone',
            'Langue',
            'Adresse',
            'Ville',
            'Code Postal',
            'Pays',
            'Statut',
            'Images',
            'Notes Client',
            'Notes Admin'
        ]);

        foreach ($orders as $order) {
            $images = $order->items->map(fn($item) => 
                ($item->image->title ?? $item->image->name) . ' (x' . $item->quantity . ')'
            )->implode(', ');

            fputcsv($handle, [
                $order->order_number,
                $order->created_at->format('d/m/Y H:i'),
                ucfirst($order->customer_type),
                $order->last_name,
                $order->first_name,
                $order->company ?? '',
                $order->email,
                $order->full_phone ?? '',
                ucfirst($order->language),
                $order->full_address,
                $order->city,
                $order->postal_code,
                $order->country,
                $order->status_label,
                $images,
                $order->customer_notes ?? '',
                $order->admin_notes ?? ''
            ]);
        }

        rewind($handle);
        $csv = stream_get_contents($handle);
        fclose($handle);

        return response()->streamDownload(function() use ($csv) {
            echo $csv;
        }, $filename, ['Content-Type' => 'text/csv']);
    }

    public function render()
    {
        $orders = ImageOrder::with(['items.image'])
            ->search($this->search)
            ->byStatus($this->statusFilter)
            ->oldest()
            ->paginate(20);

        $stats = [
            'total' => ImageOrder::count(),
            'pending' => ImageOrder::where('status', 'pending')->count(),
            'processing' => ImageOrder::where('status', 'processing')->count(),
            'completed' => ImageOrder::where('status', 'completed')->count(),
        ];

        return view('livewire.admin.order-manager', [
            'orders' => $orders,
            'stats' => $stats,
        ])->layout('components.layouts.app');
    }
}
