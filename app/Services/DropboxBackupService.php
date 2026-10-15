<?php

namespace App\Services;

use Exception;
use Illuminate\Support\Facades\Log;
use Spatie\Dropbox\Client as DropboxClient;

class DropboxBackupService
{
    protected ?DropboxClient $client = null;
    protected ?string $token = null;

    public function __construct()
    {
        $this->token = config('filesystems.disks.dropbox.authorization_token')
            ?: env('DROPBOX_AUTH_TOKEN');

        if (!empty($this->token)) {
            $this->client = new DropboxClient($this->token);
        }
    }

    /**
     * Check Dropbox Connection & Account Info
     */
    public function checkConnection(): array
    {
        if (empty($this->token)) {
            return [
                'connected' => false,
                'status' => 'Not Configured',
                'message' => 'DROPBOX_AUTH_TOKEN is not configured in .env file.',
                'account' => null,
            ];
        }

        try {
            $account = $this->client->getAccountInfo();

            return [
                'connected' => true,
                'status' => 'Connected',
                'message' => 'Successfully connected to Dropbox API.',
                'account' => [
                    'account_id' => $account['account_id'] ?? null,
                    'name' => $account['name']['display_name'] ?? 'Dropbox User',
                    'email' => $account['email'] ?? null,
                    'country' => $account['country'] ?? null,
                ],
            ];
        } catch (Exception $e) {
            Log::warning('Dropbox connection failed: ' . $e->getMessage());

            return [
                'connected' => false,
                'status' => 'Connection Failed',
                'message' => $e->getMessage(),
                'account' => null,
            ];
        }
    }

    /**
     * Get Dropbox Storage Quota Usage
     */
    public function getStorageQuota(): array
    {
        if (!$this->client) {
            return $this->getFallbackLocalQuota('Dropbox not configured in .env');
        }

        try {
            // Dropbox RPC call to users/get_space_usage
            $reflection = new \ReflectionClass($this->client);
            $method = $reflection->getMethod('rpcEndpointRequest');
            $method->setAccessible(true);
            $usage = $method->invoke($this->client, 'users/get_space_usage');

            $usedBytes = (int) ($usage['used'] ?? 0);
            $allocatedBytes = (int) ($usage['allocation']['allocated'] ?? ($usage['allocation']['individual']['allocated'] ?? 2147483648)); // default 2GB

            $usedMB = round($usedBytes / (1024 * 1024), 2);
            $totalMB = round($allocatedBytes / (1024 * 1024), 2);
            $freeMB = max(0, round($totalMB - $usedMB, 2));
            $usedPercentage = $totalMB > 0 ? min(100, round(($usedMB / $totalMB) * 100, 1)) : 0;

            $usedFormatted = $this->formatBytes($usedBytes);
            $totalFormatted = $this->formatBytes($allocatedBytes);
            $availableFormatted = $this->formatBytes(max(0, $allocatedBytes - $usedBytes));

            return [
                'is_cloud' => true,
                'status' => 'Connected',
                'account_name' => 'Dropbox Cloud Storage',
                'total_bytes' => $allocatedBytes,
                'used_bytes' => $usedBytes,
                'available_bytes' => max(0, $allocatedBytes - $usedBytes),
                'used_mb' => $usedMB,
                'total_mb' => $totalMB,
                'free_mb' => $freeMB,
                'percent_used' => $usedPercentage,
                'used_percentage' => $usedPercentage,
                'used_formatted' => $usedFormatted,
                'total_formatted' => $totalFormatted,
                'available_formatted' => $availableFormatted,
                'formatted' => [
                    'used' => $usedFormatted,
                    'total' => $totalFormatted,
                    'free' => $availableFormatted,
                ],
            ];
        } catch (Exception $e) {
            Log::warning('Dropbox getStorageQuota error: ' . $e->getMessage());
            return $this->getFallbackLocalQuota($e->getMessage());
        }
    }

    /**
     * Upload Local File to Dropbox
     */
    public function uploadFile(string $localPath, string $dropboxFileName): array
    {
        if (!$this->client) {
            return [
                'success' => false,
                'message' => 'Dropbox client not initialized. Check DROPBOX_AUTH_TOKEN.',
                'path' => null,
            ];
        }

        try {
            if (!file_exists($localPath)) {
                return [
                    'success' => false,
                    'message' => "Local source file not found at: {$localPath}",
                    'path' => null,
                ];
            }

            $remotePath = '/backups/' . ltrim($dropboxFileName, '/');
            $fileHandle = fopen($localPath, 'r');
            $this->client->upload($remotePath, $fileHandle, 'overwrite');
            if (is_resource($fileHandle)) {
                fclose($fileHandle);
            }

            return [
                'success' => true,
                'message' => 'File uploaded to Dropbox successfully.',
                'path' => $remotePath,
            ];
        } catch (Exception $e) {
            Log::error('Dropbox upload failed: ' . $e->getMessage());
            return [
                'success' => false,
                'message' => 'Dropbox upload error: ' . $e->getMessage(),
                'path' => null,
            ];
        }
    }

    /**
     * Download File from Dropbox to Local Path
     */
    public function downloadFile(string $dropboxFileName, string $destinationPath): bool
    {
        if (!$this->client) {
            return false;
        }

        try {
            $remotePath = '/backups/' . ltrim($dropboxFileName, '/');
            $contentStream = $this->client->download($remotePath);
            file_put_contents($destinationPath, stream_get_contents($contentStream));
            return true;
        } catch (Exception $e) {
            Log::error('Dropbox download failed: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Delete File from Dropbox
     */
    public function deleteFile(string $dropboxFileName): bool
    {
        if (!$this->client) {
            return false;
        }

        try {
            $remotePath = '/backups/' . ltrim($dropboxFileName, '/');
            $this->client->delete($remotePath);
            return true;
        } catch (Exception $e) {
            Log::warning('Dropbox delete failed: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Generate Direct Temporary Shareable Download Link
     */
    public function getTemporaryDownloadLink(string $dropboxFileName): ?string
    {
        if (!$this->client) {
            return null;
        }

        try {
            $remotePath = '/backups/' . ltrim($dropboxFileName, '/');
            return $this->client->getTemporaryLink($remotePath);
        } catch (Exception $e) {
            Log::warning('Dropbox getTemporaryLink failed: ' . $e->getMessage());
            return null;
        }
    }

    /**
     * Fallback Local Storage Quota Representation
     */
    protected function getFallbackLocalQuota(string $reason = ''): array
    {
        $backupDir = storage_path('app/public/backups');
        $usedBytes = 0;

        if (file_exists($backupDir)) {
            foreach (glob("{$backupDir}/*") as $file) {
                if (is_file($file)) {
                    $usedBytes += filesize($file);
                }
            }
        }

        $totalDisk = disk_total_space(storage_path()) ?: 10737418240; // 10 GB
        $freeDisk = disk_free_space(storage_path()) ?: 5368709120;
        $usedMB = round($usedBytes / (1024 * 1024), 2);
        $totalMB = round($totalDisk / (1024 * 1024), 2);
        $freeMB = round($freeDisk / (1024 * 1024), 2);

        $usedFormatted = $this->formatBytes($usedBytes);
        $totalFormatted = $this->formatBytes($totalDisk);
        $freeFormatted = $this->formatBytes($freeDisk);

        return [
            'is_cloud' => false,
            'status' => 'Local Mode (' . ($reason ?: 'Offline') . ')',
            'account_name' => 'Local Disk Storage',
            'total_bytes' => $totalDisk,
            'used_bytes' => $usedBytes,
            'available_bytes' => $freeDisk,
            'used_mb' => $usedMB,
            'total_mb' => $totalMB,
            'free_mb' => $freeMB,
            'percent_used' => $totalMB > 0 ? min(100, round(($usedMB / $totalMB) * 100, 1)) : 0,
            'used_percentage' => $totalMB > 0 ? min(100, round(($usedMB / $totalMB) * 100, 1)) : 0,
            'used_formatted' => $usedFormatted,
            'total_formatted' => $totalFormatted,
            'available_formatted' => $freeFormatted,
            'formatted' => [
                'used' => $usedFormatted,
                'total' => $totalFormatted,
                'free' => $freeFormatted,
            ],
        ];
    }

    protected function formatBytes(int $bytes): string
    {
        if ($bytes >= 1073741824) {
            return number_format($bytes / 1073741824, 2) . ' GB';
        }
        if ($bytes >= 1048576) {
            return number_format($bytes / 1048576, 2) . ' MB';
        }
        if ($bytes >= 1024) {
            return number_format($bytes / 1024, 2) . ' KB';
        }
        return $bytes . ' B';
    }
}
