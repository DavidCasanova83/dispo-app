<?php

namespace App\Services;

use App\Models\SftpConfiguration;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use League\Flysystem\Filesystem;
use League\Flysystem\PhpseclibV3\SftpAdapter;
use League\Flysystem\PhpseclibV3\SftpConnectionProvider;
use League\Flysystem\UnixVisibility\PortableVisibilityConverter;

class SftpService
{
    protected ?Filesystem $filesystem = null;
    protected ?SftpConfiguration $configuration = null;

    /**
     * Set the SFTP configuration to use
     */
    public function setConfiguration(SftpConfiguration $configuration): self
    {
        $this->configuration = $configuration;
        $this->filesystem = null; // Reset filesystem when configuration changes
        return $this;
    }

    /**
     * Test SFTP connection
     */
    public function testConnection(SftpConfiguration $configuration): array
    {
        try {
            $this->setConfiguration($configuration);
            $filesystem = $this->getFilesystem();

            // Try to list files in the remote directory
            $filesystem->listContents($configuration->remote_path);

            return [
                'success' => true,
                'message' => 'Connexion SFTP réussie !',
            ];
        } catch (\Exception $e) {
            Log::error('SFTP connection test failed', [
                'configuration_id' => $configuration->id,
                'error' => $e->getMessage(),
            ]);

            return [
                'success' => false,
                'message' => 'Échec de la connexion : ' . $e->getMessage(),
            ];
        }
    }

    /**
     * Upload a file to SFTP server
     */
    public function uploadFile(string $localPath, string $remoteFilename): array
    {
        if (!$this->configuration) {
            throw new \RuntimeException('SFTP configuration not set');
        }

        try {
            $filesystem = $this->getFilesystem();

            // Read local file
            $fileContent = file_get_contents($localPath);
            if ($fileContent === false) {
                throw new \RuntimeException('Unable to read local file');
            }

            // Build remote path
            $remotePath = rtrim($this->configuration->remote_path, '/') . '/' . $remoteFilename;

            // Upload file
            $filesystem->write($remotePath, $fileContent);

            Log::info('File uploaded to SFTP successfully', [
                'configuration_id' => $this->configuration->id,
                'remote_path' => $remotePath,
                'file_size' => strlen($fileContent),
            ]);

            return [
                'success' => true,
                'message' => 'Fichier uploadé avec succès',
                'remote_path' => $remotePath,
                'file_size' => strlen($fileContent),
            ];
        } catch (\Exception $e) {
            Log::error('SFTP upload failed', [
                'configuration_id' => $this->configuration->id,
                'local_path' => $localPath,
                'remote_filename' => $remoteFilename,
                'error' => $e->getMessage(),
            ]);

            return [
                'success' => false,
                'message' => 'Échec de l\'upload : ' . $e->getMessage(),
            ];
        }
    }

    /**
     * Check if a file exists on the SFTP server
     */
    public function fileExists(string $remotePath): bool
    {
        try {
            $filesystem = $this->getFilesystem();
            return $filesystem->fileExists($remotePath);
        } catch (\Exception $e) {
            Log::error('SFTP file exists check failed', [
                'remote_path' => $remotePath,
                'error' => $e->getMessage(),
            ]);
            return false;
        }
    }

    /**
     * List files in the remote directory
     */
    public function listFiles(string $directory = null): array
    {
        try {
            $filesystem = $this->getFilesystem();
            $path = $directory ?? $this->configuration->remote_path;

            $contents = $filesystem->listContents($path);
            $files = [];

            foreach ($contents as $item) {
                $files[] = [
                    'path' => $item['path'],
                    'type' => $item['type'],
                    'size' => $item['file_size'] ?? null,
                    'timestamp' => $item['last_modified'] ?? null,
                ];
            }

            return [
                'success' => true,
                'files' => $files,
            ];
        } catch (\Exception $e) {
            Log::error('SFTP list files failed', [
                'directory' => $directory,
                'error' => $e->getMessage(),
            ]);

            return [
                'success' => false,
                'message' => 'Échec de la récupération des fichiers : ' . $e->getMessage(),
                'files' => [],
            ];
        }
    }

    /**
     * Delete a file from the SFTP server
     */
    public function deleteFile(string $remotePath): array
    {
        try {
            $filesystem = $this->getFilesystem();
            $filesystem->delete($remotePath);

            Log::info('File deleted from SFTP successfully', [
                'configuration_id' => $this->configuration->id,
                'remote_path' => $remotePath,
            ]);

            return [
                'success' => true,
                'message' => 'Fichier supprimé avec succès',
            ];
        } catch (\Exception $e) {
            Log::error('SFTP delete failed', [
                'remote_path' => $remotePath,
                'error' => $e->getMessage(),
            ]);

            return [
                'success' => false,
                'message' => 'Échec de la suppression : ' . $e->getMessage(),
            ];
        }
    }

    /**
     * Get or create the Flysystem filesystem instance
     */
    protected function getFilesystem(): Filesystem
    {
        if ($this->filesystem !== null) {
            return $this->filesystem;
        }

        if (!$this->configuration) {
            throw new \RuntimeException('SFTP configuration not set');
        }

        // Build connection configuration
        $connectionConfig = [
            'host' => $this->configuration->host,
            'port' => $this->configuration->port,
            'username' => $this->configuration->username,
            'timeout' => config('sftp.timeout', 30),
        ];

        // Add authentication method
        if (!empty($this->configuration->password)) {
            $connectionConfig['password'] = $this->configuration->password;
        } elseif (!empty($this->configuration->private_key)) {
            $connectionConfig['privateKey'] = $this->configuration->private_key;
        } else {
            throw new \RuntimeException('No authentication method configured');
        }

        // Create SFTP connection provider
        $provider = SftpConnectionProvider::fromArray($connectionConfig);

        // Create SFTP adapter
        $adapter = new SftpAdapter(
            $provider,
            $this->configuration->remote_path,
            PortableVisibilityConverter::fromArray([
                'file' => [
                    'public' => 0644,
                    'private' => 0600,
                ],
                'dir' => [
                    'public' => 0755,
                    'private' => 0700,
                ],
            ])
        );

        // Create and cache filesystem
        $this->filesystem = new Filesystem($adapter);

        return $this->filesystem;
    }

    /**
     * Validate if a file is a valid PDF
     */
    public function validatePdfFile(string $filePath): array
    {
        // Check if file exists
        if (!file_exists($filePath)) {
            return [
                'valid' => false,
                'message' => 'Le fichier n\'existe pas',
            ];
        }

        // Check file size
        $maxSize = config('sftp.max_file_size', 10240) * 1024; // Convert KB to bytes
        $fileSize = filesize($filePath);

        if ($fileSize > $maxSize) {
            return [
                'valid' => false,
                'message' => 'Le fichier est trop volumineux (max: ' . config('sftp.max_file_size', 10240) . ' KB)',
            ];
        }

        // Check MIME type
        $mimeType = mime_content_type($filePath);
        if ($mimeType !== 'application/pdf') {
            return [
                'valid' => false,
                'message' => 'Le fichier doit être au format PDF',
            ];
        }

        // Check file extension
        $extension = strtolower(pathinfo($filePath, PATHINFO_EXTENSION));
        if ($extension !== 'pdf') {
            return [
                'valid' => false,
                'message' => 'L\'extension du fichier doit être .pdf',
            ];
        }

        return [
            'valid' => true,
            'message' => 'Fichier PDF valide',
            'size' => $fileSize,
        ];
    }

    /**
     * Generate a unique filename for upload
     */
    public function generateUniqueFilename(string $originalFilename): string
    {
        $extension = pathinfo($originalFilename, PATHINFO_EXTENSION);
        $filename = pathinfo($originalFilename, PATHINFO_FILENAME);

        // Sanitize filename
        $filename = preg_replace('/[^a-zA-Z0-9_-]/', '_', $filename);

        // Add timestamp and random string
        $uniqueFilename = $filename . '_' . time() . '_' . substr(md5(uniqid()), 0, 8) . '.' . $extension;

        return $uniqueFilename;
    }
}
