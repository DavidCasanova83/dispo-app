<?php

namespace App\Services;

use App\Models\SftpConfiguration;
use App\Models\SftpUpload;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use phpseclib3\Net\SFTP;

class SftpService
{
    protected ?SFTP $connection = null;
    protected ?SftpConfiguration $config = null;

    /**
     * Connect to SFTP server
     */
    public function connect(SftpConfiguration $config): bool
    {
        try {
            $this->config = $config;
            $this->connection = new SFTP($config->host, $config->port, $config->timeout);

            // Login with password or private key
            if ($config->private_key) {
                // TODO: Implement private key authentication if needed
                // For now, use password authentication
                $success = $this->connection->login($config->username, $config->password);
            } else {
                $success = $this->connection->login($config->username, $config->password);
            }

            if (!$success) {
                Log::error('SFTP connection failed', [
                    'host' => $config->host,
                    'username' => $config->username,
                ]);
                return false;
            }

            // Change to remote directory
            if (!$this->connection->chdir($config->remote_path)) {
                Log::warning('SFTP: Could not change to remote directory', [
                    'path' => $config->remote_path,
                ]);
                // Try to create the directory
                $this->connection->mkdir($config->remote_path, 0755, true);
                if (!$this->connection->chdir($config->remote_path)) {
                    Log::error('SFTP: Failed to create or access remote directory', [
                        'path' => $config->remote_path,
                    ]);
                    return false;
                }
            }

            return true;
        } catch (\Exception $e) {
            Log::error('SFTP connection exception', [
                'message' => $e->getMessage(),
                'host' => $config->host,
            ]);
            return false;
        }
    }

    /**
     * Test connection to SFTP server
     */
    public function testConnection(SftpConfiguration $config): bool
    {
        $connected = $this->connect($config);
        $this->disconnect();
        return $connected;
    }

    /**
     * Upload a PDF file to SFTP server
     */
    public function uploadFile(UploadedFile $file, int $userId): array
    {
        // Get active configuration
        $config = SftpConfiguration::getActive();

        if (!$config) {
            return [
                'success' => false,
                'message' => 'Aucune configuration SFTP active trouvée.',
            ];
        }

        // Validate file type
        if ($file->getClientOriginalExtension() !== 'pdf' || $file->getMimeType() !== 'application/pdf') {
            return [
                'success' => false,
                'message' => 'Seuls les fichiers PDF sont acceptés.',
            ];
        }

        // Connect to SFTP
        if (!$this->connect($config)) {
            return [
                'success' => false,
                'message' => 'Impossible de se connecter au serveur SFTP.',
            ];
        }

        try {
            // Generate unique filename
            $originalFilename = $file->getClientOriginalName();
            $filename = Str::slug(pathinfo($originalFilename, PATHINFO_FILENAME)) . '_' . time() . '.pdf';
            $remotePath = rtrim($config->remote_path, '/') . '/' . $filename;

            // Read file contents
            $contents = file_get_contents($file->getRealPath());

            // Upload file
            if (!$this->connection->put($filename, $contents)) {
                throw new \Exception('Failed to upload file to SFTP server');
            }

            // Log successful upload
            $upload = SftpUpload::create([
                'user_id' => $userId,
                'sftp_configuration_id' => $config->id,
                'original_filename' => $originalFilename,
                'remote_filename' => $filename,
                'remote_path' => $remotePath,
                'file_size' => $file->getSize(),
                'status' => 'success',
            ]);

            $this->disconnect();

            return [
                'success' => true,
                'message' => 'Fichier uploadé avec succès.',
                'upload' => $upload,
            ];
        } catch (\Exception $e) {
            // Log failed upload
            SftpUpload::create([
                'user_id' => $userId,
                'sftp_configuration_id' => $config->id,
                'original_filename' => $file->getClientOriginalName(),
                'remote_filename' => $filename ?? 'unknown',
                'remote_path' => $remotePath ?? 'unknown',
                'file_size' => $file->getSize(),
                'status' => 'failed',
                'error_message' => $e->getMessage(),
            ]);

            $this->disconnect();

            Log::error('SFTP upload failed', [
                'message' => $e->getMessage(),
                'file' => $file->getClientOriginalName(),
            ]);

            return [
                'success' => false,
                'message' => 'Erreur lors de l\'upload: ' . $e->getMessage(),
            ];
        }
    }

    /**
     * List files in remote directory
     */
    public function listFiles(): array
    {
        if (!$this->connection) {
            return [];
        }

        try {
            $files = $this->connection->nlist();
            return is_array($files) ? array_filter($files, fn($f) => $f !== '.' && $f !== '..') : [];
        } catch (\Exception $e) {
            Log::error('SFTP list files failed', ['message' => $e->getMessage()]);
            return [];
        }
    }

    /**
     * Disconnect from SFTP server
     */
    public function disconnect(): void
    {
        if ($this->connection) {
            $this->connection->disconnect();
            $this->connection = null;
        }
    }

    /**
     * Destructor to ensure connection is closed
     */
    public function __destruct()
    {
        $this->disconnect();
    }
}
