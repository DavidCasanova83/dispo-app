<?php

return [

    /*
    |--------------------------------------------------------------------------
    | SFTP Configuration
    |--------------------------------------------------------------------------
    |
    | This file contains configuration options for the SFTP upload feature.
    | These settings control file upload behavior and connection parameters.
    |
    */

    /**
     * Connection timeout in seconds
     */
    'timeout' => env('SFTP_TIMEOUT', 30),

    /**
     * Maximum file size in KB
     */
    'max_file_size' => env('SFTP_MAX_FILE_SIZE', 10240), // 10 MB by default

    /**
     * Allowed file extensions for upload
     */
    'allowed_extensions' => ['pdf'],

    /**
     * Allowed MIME types for upload
     */
    'allowed_mime_types' => ['application/pdf'],

    /**
     * Local temporary storage path for uploads before sending to SFTP
     */
    'local_storage_path' => storage_path('app/sftp_uploads'),

    /**
     * Number of retry attempts for failed uploads
     */
    'retry_attempts' => 3,

    /**
     * Delete local file after successful upload
     */
    'delete_after_upload' => true,

    /**
     * Log all SFTP operations
     */
    'enable_logging' => true,

];
