<?php

namespace App\Livewire\Admin;

use App\Models\SftpConfiguration as SftpConfigModel;
use App\Services\SftpService;
use Livewire\Attributes\Validate;
use Livewire\Component;
use Livewire\WithPagination;

class SftpConfiguration extends Component
{
    use WithPagination;

    // Form fields
    #[Validate('required|string|max:255')]
    public $name = '';

    #[Validate('required|string|max:255')]
    public $host = '';

    #[Validate('required|integer|min:1|max:65535')]
    public $port = 22;

    #[Validate('required|string|max:255')]
    public $username = '';

    #[Validate('nullable|string')]
    public $password = '';

    #[Validate('nullable|string')]
    public $private_key = '';

    #[Validate('required|string|max:255')]
    public $remote_path = '/';

    #[Validate('boolean')]
    public $active = true;

    // UI state
    public $editingId = null;
    public $showModal = false;
    public $testingConnection = false;
    public $testResult = null;

    protected $queryString = [
        'page' => ['except' => 1],
    ];

    /**
     * Open modal to create new configuration
     */
    public function create()
    {
        $this->resetForm();
        $this->showModal = true;
    }

    /**
     * Open modal to edit existing configuration
     */
    public function edit($id)
    {
        $config = SftpConfigModel::findOrFail($id);

        $this->editingId = $config->id;
        $this->name = $config->name;
        $this->host = $config->host;
        $this->port = $config->port;
        $this->username = $config->username;
        $this->password = ''; // Don't populate password for security
        $this->private_key = ''; // Don't populate private key for security
        $this->remote_path = $config->remote_path;
        $this->active = $config->active;

        $this->showModal = true;
    }

    /**
     * Save configuration (create or update)
     */
    public function save()
    {
        $this->validate();

        // Ensure at least one authentication method is provided
        if (empty($this->password) && empty($this->private_key) && !$this->editingId) {
            $this->addError('password', 'Vous devez fournir un mot de passe ou une clé privée.');
            return;
        }

        $data = [
            'name' => $this->name,
            'host' => $this->host,
            'port' => $this->port,
            'username' => $this->username,
            'remote_path' => $this->remote_path,
            'active' => $this->active,
            'created_by' => auth()->id(),
        ];

        // Only update password/key if provided
        if (!empty($this->password)) {
            $data['password'] = $this->password;
            $data['private_key'] = null;
        } elseif (!empty($this->private_key)) {
            $data['private_key'] = $this->private_key;
            $data['password'] = null;
        }

        if ($this->editingId) {
            $config = SftpConfigModel::findOrFail($this->editingId);

            // If no new credentials provided, keep existing ones
            if (empty($this->password) && empty($this->private_key)) {
                unset($data['password'], $data['private_key']);
            }

            $config->update($data);
            session()->flash('message', 'Configuration mise à jour avec succès.');
        } else {
            SftpConfigModel::create($data);
            session()->flash('message', 'Configuration créée avec succès.');
        }

        $this->closeModal();
        $this->resetPage();
    }

    /**
     * Delete configuration
     */
    public function delete($id)
    {
        $config = SftpConfigModel::findOrFail($id);
        $config->delete();

        session()->flash('message', 'Configuration supprimée avec succès.');
        $this->resetPage();
    }

    /**
     * Test SFTP connection
     */
    public function testConnection($id)
    {
        $this->testingConnection = true;
        $this->testResult = null;

        try {
            $config = SftpConfigModel::findOrFail($id);
            $sftpService = app(SftpService::class);

            $result = $sftpService->testConnection($config);

            if ($result['success']) {
                $config->update(['last_test_at' => now()]);
            }

            $this->testResult = $result;
            session()->flash('testResult', $result);

        } catch (\Exception $e) {
            $this->testResult = [
                'success' => false,
                'message' => 'Erreur lors du test : ' . $e->getMessage(),
            ];
            session()->flash('testResult', $this->testResult);
        } finally {
            $this->testingConnection = false;
        }
    }

    /**
     * Toggle configuration active status
     */
    public function toggleActive($id)
    {
        $config = SftpConfigModel::findOrFail($id);
        $config->update(['active' => !$config->active]);

        session()->flash('message', 'Statut mis à jour avec succès.');
    }

    /**
     * Close modal and reset form
     */
    public function closeModal()
    {
        $this->showModal = false;
        $this->resetForm();
    }

    /**
     * Reset form fields
     */
    protected function resetForm()
    {
        $this->reset([
            'editingId',
            'name',
            'host',
            'port',
            'username',
            'password',
            'private_key',
            'remote_path',
            'active',
            'testResult',
        ]);
        $this->port = 22;
        $this->remote_path = '/';
        $this->active = true;
        $this->resetErrorBag();
    }

    /**
     * Render component
     */
    public function render()
    {
        $configurations = SftpConfigModel::with('creator')
            ->withCount('uploads')
            ->latest()
            ->paginate(10);

        return view('livewire.admin.sftp-configuration', [
            'configurations' => $configurations,
        ])->layout('components.layouts.app');
    }
}
