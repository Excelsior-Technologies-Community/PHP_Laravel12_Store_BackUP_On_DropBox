<?php

namespace App\Services;

use App\Models\BackupHistory;
use App\Models\BackupRestoreHistory;
use Exception;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use ZipArchive;

class BackupRestoreService
{
    protected DropboxBackupService $dropboxService;

    public function __construct(DropboxBackupService $dropboxService)
    {
        $this->dropboxService = $dropboxService;
    }

    /**
     * Restore database from Backup ZIP
     */
    public function restoreDatabase(BackupHistory $backup): array
    {
        $startTime = microtime(true);
        $localPath = storage_path('app/public/backups/' . $backup->file_name);
        $tempPath = storage_path('app/temp/' . $backup->file_name);
        $isTemporaryDownload = false;

        if (!file_exists($localPath)) {
            // Try fetching from Dropbox if cloud copy is available
            if ($backup->isAvailableOnDropbox()) {
                if (!file_exists(dirname($tempPath))) {
                    mkdir(dirname($tempPath), 0755, true);
                }
                $downloaded = $this->dropboxService->downloadFile($backup->file_name, $tempPath);
                if ($downloaded && file_exists($tempPath)) {
                    $localPath = $tempPath;
                    $isTemporaryDownload = true;
                }
            }
        }

        if (!file_exists($localPath)) {
            $duration = round(microtime(true) - $startTime, 2);
            $remarks = 'Backup archive file is missing locally and could not be retrieved from Dropbox.';

            BackupRestoreHistory::create([
                'backup_history_id' => $backup->id,
                'file_name' => $backup->file_name,
                'status' => 'Failed',
                'duration_seconds' => $duration,
                'remarks' => $remarks,
                'restored_at' => now(),
            ]);

            return ['success' => false, 'message' => $remarks];
        }

        $zip = new ZipArchive();
        if ($zip->open($localPath) !== true) {
            $duration = round(microtime(true) - $startTime, 2);
            $remarks = 'ZIP archive is corrupted or unreadable.';

            BackupRestoreHistory::create([
                'backup_history_id' => $backup->id,
                'file_name' => $backup->file_name,
                'status' => 'Failed',
                'duration_seconds' => $duration,
                'remarks' => $remarks,
                'restored_at' => now(),
            ]);

            return ['success' => false, 'message' => $remarks];
        }

        // Search for .sql file inside the ZIP archive
        $sqlIndex = -1;
        $sqlFileName = '';

        for ($i = 0; $i < $zip->numFiles; $i++) {
            $stat = $zip->statIndex($i);
            if (str_ends_with(strtolower($stat['name']), '.sql')) {
                $sqlIndex = $i;
                $sqlFileName = $stat['name'];
                break;
            }
        }

        if ($sqlIndex === -1) {
            $zip->close();
            if ($isTemporaryDownload && file_exists($tempPath)) {
                @unlink($tempPath);
            }

            $duration = round(microtime(true) - $startTime, 2);
            $remarks = 'No SQL dump file found inside the selected backup archive.';

            BackupRestoreHistory::create([
                'backup_history_id' => $backup->id,
                'file_name' => $backup->file_name,
                'status' => 'Failed',
                'duration_seconds' => $duration,
                'remarks' => $remarks,
                'restored_at' => now(),
            ]);

            return ['success' => false, 'message' => $remarks];
        }

        // Extract and run SQL
        $sqlContent = $zip->getFromIndex($sqlIndex);
        $zip->close();

        if ($isTemporaryDownload && file_exists($tempPath)) {
            @unlink($tempPath);
        }

        if (empty($sqlContent)) {
            $duration = round(microtime(true) - $startTime, 2);
            $remarks = 'SQL dump file is empty.';

            BackupRestoreHistory::create([
                'backup_history_id' => $backup->id,
                'file_name' => $backup->file_name,
                'status' => 'Failed',
                'duration_seconds' => $duration,
                'remarks' => $remarks,
                'restored_at' => now(),
            ]);

            return ['success' => false, 'message' => $remarks];
        }

        try {
            // Execute SQL script
            DB::unprepared($sqlContent);

            $duration = round(microtime(true) - $startTime, 2);
            $remarks = "Database successfully restored from '{$sqlFileName}' in {$duration}s.";

            BackupRestoreHistory::create([
                'backup_history_id' => $backup->id,
                'file_name' => $backup->file_name,
                'status' => 'Success',
                'duration_seconds' => $duration,
                'remarks' => $remarks,
                'restored_at' => now(),
            ]);

            return [
                'success' => true,
                'message' => $remarks,
                'duration' => $duration,
            ];
        } catch (Exception $e) {
            Log::error('Database restore error: ' . $e->getMessage());
            $duration = round(microtime(true) - $startTime, 2);
            $remarks = 'SQL execution error: ' . $e->getMessage();

            BackupRestoreHistory::create([
                'backup_history_id' => $backup->id,
                'file_name' => $backup->file_name,
                'status' => 'Failed',
                'duration_seconds' => $duration,
                'remarks' => $remarks,
                'restored_at' => now(),
            ]);

            return ['success' => false, 'message' => $remarks];
        }
    }
}
