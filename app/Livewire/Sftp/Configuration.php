<?php

namespace App\Livewire\Sftp;

use App\Models\SftpConfiguration;
use App\Services\SftpService;
use Livewire\Component;
use Flux\Flux;

class Configuration extends Component
{
    public ?SftpConfiguration $configuration = null;
    public string $name = '';
    public string $host = '';
    public int $port = 22;
    public string $username = '';
    public string $password = '';
    public string $remote_path = '/';
    public int $timeout = 30;
    public bool $is_active = true;
    public bool $testing = false;

    public function mount(): void
    {
        // Load existing configuration
        $this->configuration = SftpConfiguration::getActive();

        if ($this->configuration) {
            $this->name = $this->configuration->name;
            $this->host = $this->configuration->host;
            $this->port = $this->configuration->port;
            $this->username = $this->configuration->username;
            // Don't load password for security reasons
            $this->remote_path = $this->configuration->remote_path;
            $this->timeout = $this->configuration->timeout;
            $this->is_active = $this->configuration->is_active;
        } else {
            $this->name = 'Configuration SFTP';
        }
    }

    public function save(): void
    {
        $this->validate([
            'name' => 'required|string|max:255',
            'host' => 'required|string|max:255',
            'port' => 'required|integer|min:1|max:65535',
            'username' => 'required|string|max:255',
            'password' => $this->configuration ? 'nullable|string' : 'required|string',
            'remote_path' => 'required|string|max:500',
            'timeout' => 'required|integer|min:5|max:120',
        ], [
            'name.required' => 'Le nom est obligatoire.',
            'host.required' => 'L\'hôte est obligatoire.',
            'port.required' => 'Le port est obligatoire.',
            'port.integer' => 'Le port doit être un nombre.',
            'port.min' => 'Le port doit être supérieur à 0.',
            'port.max' => 'Le port ne peut pas dépasser 65535.',
            'username.required' => 'Le nom d\'utilisateur est obligatoire.',
            'password.required' => 'Le mot de passe est obligatoire.',
            'remote_path.required' => 'Le chemin distant est obligatoire.',
            'timeout.required' => 'Le timeout est obligatoire.',
            'timeout.min' => 'Le timeout doit être au minimum 5 secondes.',
            'timeout.max' => 'Le timeout ne peut pas dépasser 120 secondes.',
        ]);

        try {
            if ($this->configuration) {
                // Update existing configuration
                $data = [
                    'name' => $this->name,
                    'host' => $this->host,
                    'port' => $this->port,
                    'username' => $this->username,
                    'remote_path' => $this->remote_path,
                    'timeout' => $this->timeout,
                    'is_active' => $this->is_active,
                ];

                // Only update password if provided
                if (!empty($this->password)) {
                    $data['password'] = $this->password;
                }

                $this->configuration->update($data);
            } else {
                // Create new configuration
                // Deactivate all other configurations first
                SftpConfiguration::query()->update(['is_active' => false]);

                $this->configuration = SftpConfiguration::create([
                    'name' => $this->name,
                    'host' => $this->host,
                    'port' => $this->port,
                    'username' => $this->username,
                    'password' => $this->password,
                    'remote_path' => $this->remote_path,
                    'timeout' => $this->timeout,
                    'is_active' => $this->is_active,
                ]);
            }

            Flux::toast('Configuration SFTP enregistrée avec succès.', variant: 'success');

            // Clear password field after save
            $this->password = '';
        } catch (\Exception $e) {
            Flux::toast('Erreur lors de l\'enregistrement: ' . $e->getMessage(), variant: 'danger');
        }
    }

    public function testConnection(): void
    {
        $this->validate([
            'host' => 'required|string',
            'port' => 'required|integer',
            'username' => 'required|string',
            'password' => $this->configuration && empty($this->password) ? 'nullable' : 'required',
        ]);

        $this->testing = true;

        try {
            // Create temporary configuration for testing
            $testConfig = new SftpConfiguration([
                'host' => $this->host,
                'port' => $this->port,
                'username' => $this->username,
                'password' => !empty($this->password) ? $this->password : ($this->configuration?->password ?? ''),
                'remote_path' => $this->remote_path,
                'timeout' => $this->timeout,
            ]);

            $sftpService = app(SftpService::class);
            $success = $sftpService->testConnection($testConfig);

            if ($success) {
                Flux::toast('Connexion SFTP réussie !', variant: 'success');
            } else {
                Flux::toast('Échec de la connexion SFTP. Vérifiez vos paramètres.', variant: 'danger');
            }
        } catch (\Exception $e) {
            Flux::toast('Erreur de connexion: ' . $e->getMessage(), variant: 'danger');
        } finally {
            $this->testing = false;
        }
    }

    public function render()
    {
        return view('livewire.sftp.configuration');
    }
}
