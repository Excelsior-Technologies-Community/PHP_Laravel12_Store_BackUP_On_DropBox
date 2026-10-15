<?php

namespace App\Http\Controllers;

use App\Models\BackupCleanupHistory;
use App\Models\BackupHistory;
use App\Models\BackupRestoreHistory;
use App\Models\BackupVerificationHistory;
use App\Services\BackupRestoreService;
use App\Services\DropboxBackupService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\URL;
use ZipArchive;

class BackupController extends Controller
{
    protected DropboxBackupService $dropboxService;
    protected BackupRestoreService $restoreService;

    public function __construct(DropboxBackupService $dropboxService, BackupRestoreService $restoreService)
    {
        $this->dropboxService = $dropboxService;
        $this->restoreService = $restoreService;
    }

    /**
     * Dashboard & Backups Listing
     */
    public function index(Request $request)
    {
        $search = $request->search;
        $diskFilter = $request->disk;

        $backups = BackupHistory::when($search, function ($query) use ($search) {
            $query->where('file_name', 'like', "%{$search}%");
        })
        ->when($diskFilter, function ($query) use ($diskFilter) {
            $query->where('storage_disk', $diskFilter);
        })
        ->latest()
        ->paginate(10);

        $totalBackups = BackupHistory::count();
        $todayBackups = BackupHistory::whereDate('created_at', Carbon::today())->count();
        $successBackups = BackupHistory::where('status', 'Success')->count();
        $failedBackups = BackupHistory::where('status', 'Failed')->count();
        $avgDuration = round((float) BackupHistory::where('duration_seconds', '>', 0)->avg('duration_seconds') ?: 0, 2);

        // Dropbox Cloud Status and Quota
        $dropboxStatus = $this->dropboxService->checkConnection();
        $dropboxQuota = $this->dropboxService->getStorageQuota();

        return view('backup.index', compact(
            'backups',
            'search',
            'diskFilter',
            'totalBackups',
            'todayBackups',
            'successBackups',
            'failedBackups',
            'avgDuration',
            'dropboxStatus',
            'dropboxQuota'
        ));
    }

    /**
     * Upload / Create Backup View
     */
    public function create()
    {
        $dropboxStatus = $this->dropboxService->checkConnection();
        return view('backup.create', compact('dropboxStatus'));
    }

    /**
     * Store Uploaded Backup (Supports Local, Dropbox, and Dual Storage)
     */
    public function store(Request $request)
    {
        $request->validate([
            'backup_file' => 'required|file|mimes:zip',
            'storage_disk' => 'required|in:local,dropbox,both',
        ]);

        $startTime = microtime(true);
        $file = $request->file('backup_file');
        $fileName = time() . '_' . preg_replace('/[^a-zA-Z0-9_\-\.]/', '_', $file->getClientOriginalName());
        $fileSizeKB = round($file->getSize() / 1024, 2) . ' KB';
        $checksum = hash_file('sha256', $file->getRealPath());

        $selectedDisk = $request->input('storage_disk', 'local');
        $dropboxPath = null;
        $status = 'Success';
        $notes = [];

        // 1. Store Locally if selected or dual
        if (in_array($selectedDisk, ['local', 'both'])) {
            Storage::disk('public')->putFileAs('backups', $file, $fileName);
            $notes[] = 'Saved locally';
        }

        // 2. Upload to Dropbox if selected or dual
        if (in_array($selectedDisk, ['dropbox', 'both'])) {
            $localFullPath = in_array($selectedDisk, ['local', 'both'])
                ? storage_path('app/public/backups/' . $fileName)
                : $file->getRealPath();

            $uploadResult = $this->dropboxService->uploadFile($localFullPath, $fileName);

            if ($uploadResult['success']) {
                $dropboxPath = $uploadResult['path'];
                $notes[] = 'Synced to Dropbox';
            } else {
                if ($selectedDisk === 'dropbox') {
                    $status = 'Failed';
                }
                $notes[] = 'Dropbox upload note: ' . $uploadResult['message'];
            }
        }

        $duration = round(microtime(true) - $startTime, 2);

        BackupHistory::create([
            'file_name' => $fileName,
            'file_size' => $fileSizeKB,
            'status' => $status,
            'storage_disk' => $selectedDisk,
            'dropbox_path' => $dropboxPath,
            'duration_seconds' => $duration,
            'checksum_sha256' => $checksum,
            'verification_status' => 'Pending',
            'verified_at' => null,
        ]);

        $msg = 'Backup created successfully (' . implode(', ', $notes) . '). Took ' . $duration . 's.';
        return redirect()->route('backup.index')->with('success', $msg);
    }

    /**
     * Download Backup File
     */
    public function download($id)
    {
        $backup = BackupHistory::findOrFail($id);
        $localPath = 'backups/' . $backup->file_name;

        if (Storage::disk('public')->exists($localPath)) {
            return Storage::disk('public')->download($localPath);
        }

        // If not stored locally but present on Dropbox, fetch temporary link
        if ($backup->isAvailableOnDropbox()) {
            $tempLink = $this->dropboxService->getTemporaryDownloadLink($backup->file_name);
            if ($tempLink) {
                return redirect()->away($tempLink);
            }
        }

        return redirect()->back()->with('error', 'Backup file not found in storage.');
    }

    /**
     * Generate Direct Secure Temporary Share Link
     */
    public function shareLink($id)
    {
        $backup = BackupHistory::findOrFail($id);

        if ($backup->isAvailableOnDropbox()) {
            $cloudUrl = $this->dropboxService->getTemporaryDownloadLink($backup->file_name);
            if ($cloudUrl) {
                return response()->json([
                    'success' => true,
                    'type' => 'Dropbox Cloud Link (4 Hours Expiry)',
                    'url' => $cloudUrl,
                ]);
            }
        }

        // Fallback to local signed temporary URL (valid 60 minutes)
        $signedUrl = URL::temporarySignedRoute(
            'backup.download',
            now()->addMinutes(60),
            ['id' => $backup->id]
        );

        return response()->json([
            'success' => true,
            'type' => 'Secure Local Signed URL (60 Min Expiry)',
            'url' => $signedUrl,
        ]);
    }

    /**
     * Sync Local Backup to Dropbox
     */
    public function syncToDropbox($id)
    {
        $backup = BackupHistory::findOrFail($id);
        $localPath = storage_path('app/public/backups/' . $backup->file_name);

        if (!file_exists($localPath)) {
            return redirect()->back()->with('error', 'Local backup file does not exist to sync.');
        }

        $result = $this->dropboxService->uploadFile($localPath, $backup->file_name);

        if ($result['success']) {
            $backup->update([
                'storage_disk' => 'both',
                'dropbox_path' => $result['path'],
            ]);

            return redirect()->back()->with('success', 'Backup successfully synced to Dropbox Cloud.');
        }

        return redirect()->back()->with('error', 'Failed to sync to Dropbox: ' . $result['message']);
    }

    /**
     * Live Dropbox Connection Status Checker
     */
    public function testDropboxConnection()
    {
        $status = $this->dropboxService->checkConnection();
        return response()->json($status);
    }

    /**
     * Live Dropbox Quota API
     */
    public function getDropboxQuota()
    {
        $quota = $this->dropboxService->getStorageQuota();
        return response()->json($quota);
    }

    /**
     * One-Click Database Restore
     */
    public function restore(Request $request, $id)
    {
        $backup = BackupHistory::findOrFail($id);

        $result = $this->restoreService->restoreDatabase($backup);

        if ($result['success']) {
            return redirect()->back()->with('success', $result['message']);
        }

        return redirect()->back()->with('error', $result['message']);
    }

    /**
     * View Database Restore Histories
     */
    public function restoreHistory()
    {
        $histories = BackupRestoreHistory::with('backup')->latest()->paginate(10);
        return view('backup.restore-history', compact('histories'));
    }

    /**
     * Verify Backup Integrity
     */
    public function verify($id)
    {
        $backup = BackupHistory::findOrFail($id);
        $path = storage_path('app/public/backups/' . $backup->file_name);
        $tempPath = storage_path('app/temp/' . $backup->file_name);
        $isTemporaryDownload = false;

        if (!file_exists($path) && $backup->isAvailableOnDropbox()) {
            if (!file_exists(dirname($tempPath))) {
                mkdir(dirname($tempPath), 0755, true);
            }
            if ($this->dropboxService->downloadFile($backup->file_name, $tempPath)) {
                $path = $tempPath;
                $isTemporaryDownload = true;
            }
        }

        $status = 'Pending';
        $remarks = '';

        if (!file_exists($path)) {
            $status = 'Missing';
            $remarks = 'Backup archive file is missing from both local and cloud storage.';
        } else {
            $zip = new ZipArchive();
            if ($zip->open($path) === true) {
                $status = 'Verified';
                $remarks = 'ZIP archive verified successfully (' . $zip->numFiles . ' files checked).';
                $zip->close();
            } else {
                $status = 'Corrupted';
                $remarks = 'ZIP archive is corrupted or unreadable.';
            }
        }

        if ($isTemporaryDownload && file_exists($tempPath)) {
            @unlink($tempPath);
        }

        $backup->update([
            'verification_status' => $status,
            'verified_at' => now(),
        ]);

        BackupVerificationHistory::create([
            'backup_history_id' => $backup->id,
            'verification_status' => $status,
            'remarks' => $remarks,
            'verified_at' => now(),
        ]);

        return redirect()->route('backup.index')
            ->with('success', 'Backup verification completed: ' . $status);
    }

    /**
     * Verification History
     */
    public function verificationHistory($id)
    {
        $backup = BackupHistory::findOrFail($id);
        $histories = $backup->verificationHistories()->paginate(10);

        return view('backup.verification-history', compact('backup', 'histories'));
    }

    /**
     * Retention Policy Cleanup
     */
    public function cleanup(Request $request)
    {
        $request->validate([
            'retention_limit' => 'required|integer|min:1',
        ]);

        $retentionLimit = (int) $request->retention_limit;
        $backups = BackupHistory::latest()->get();
        $deleteBackups = $backups->slice($retentionLimit);

        if ($deleteBackups->isEmpty()) {
            BackupCleanupHistory::create([
                'retention_limit' => $retentionLimit,
                'deleted_backups' => 0,
                'freed_space' => '0 MB',
                'status' => 'Success',
                'remarks' => 'No cleanup required. Backup count is within limit.',
                'cleaned_at' => now(),
            ]);

            return redirect()->back()->with('info', 'No cleanup required. Backups are already within retention limit.');
        }

        $deletedCount = 0;
        $freedSpace = 0;

        foreach ($deleteBackups as $backup) {
            $filePath = 'backups/' . $backup->file_name;

            if (Storage::disk('public')->exists($filePath)) {
                $freedSpace += Storage::disk('public')->size($filePath);
                Storage::disk('public')->delete($filePath);
            }

            if ($backup->isAvailableOnDropbox()) {
                $this->dropboxService->deleteFile($backup->file_name);
            }

            $backup->delete();
            $deletedCount++;
        }

        $freedSpaceMB = round($freedSpace / 1024 / 1024, 2) . ' MB';

        BackupCleanupHistory::create([
            'retention_limit' => $retentionLimit,
            'deleted_backups' => $deletedCount,
            'freed_space' => $freedSpaceMB,
            'status' => 'Success',
            'remarks' => 'Old backups removed from local and cloud storage successfully.',
            'cleaned_at' => now(),
        ]);

        return redirect()->back()->with('success', "Cleanup completed: {$deletedCount} old backups removed, {$freedSpaceMB} freed.");
    }

    /**
     * Cleanup History
     */
    public function cleanupHistory()
    {
        $histories = BackupCleanupHistory::latest()->paginate(10);
        return view('backup.cleanup-history', compact('histories'));
    }

    /**
     * Delete Backup
     */
    public function destroy($id)
    {
        $backup = BackupHistory::findOrFail($id);

        if (Storage::disk('public')->exists('backups/' . $backup->file_name)) {
            Storage::disk('public')->delete('backups/' . $backup->file_name);
        }

        if ($backup->isAvailableOnDropbox()) {
            $this->dropboxService->deleteFile($backup->file_name);
        }

        $backup->delete();

        return redirect()->back()->with('success', 'Backup deleted from all locations successfully.');
    }

    /**
     * Analytics & Chart Data Endpoint (Last 30 Days Growth & Health)
     */
    public function analytics()
    {
        $startDate = Carbon::today()->subDays(29);

        // Daily Backup Count & Size Growth over last 30 days
        $days = [];
        $sizesMB = [];
        $counts = [];

        for ($i = 0; $i < 30; $i++) {
            $date = $startDate->copy()->addDays($i);
            $dateStr = $date->format('M d');
            $days[] = $dateStr;

            $dayBackups = BackupHistory::whereDate('created_at', $date->toDateString())->get();
            $counts[] = $dayBackups->count();

            // Approximate size in MB
            $totalKb = 0;
            foreach ($dayBackups as $b) {
                if (preg_match('/([0-9\.]+)\s*KB/i', $b->file_size, $m)) {
                    $totalKb += (float) $m[1];
                } elseif (preg_match('/([0-9\.]+)\s*MB/i', $b->file_size, $m)) {
                    $totalKb += (float) $m[1] * 1024;
                }
            }
            $sizesMB[] = round($totalKb / 1024, 2);
        }

        // Storage Location Distribution
        $localOnly = BackupHistory::where('storage_disk', 'local')->count();
        $dropboxOnly = BackupHistory::where('storage_disk', 'dropbox')->count();
        $bothDisks = BackupHistory::where('storage_disk', 'both')->count();

        // Verification breakdown
        $verifiedCount = BackupHistory::where('verification_status', 'Verified')->count();
        $corruptedCount = BackupHistory::where('verification_status', 'Corrupted')->count();
        $pendingCount = BackupHistory::where('verification_status', 'Pending')->count();

        return response()->json([
            'chart' => [
                'labels' => $days,
                'sizes_mb' => $sizesMB,
                'counts' => $counts,
            ],
            'distribution' => [
                'local' => $localOnly,
                'dropbox' => $dropboxOnly,
                'dual' => $bothDisks,
            ],
            'verification' => [
                'verified' => $verifiedCount,
                'corrupted' => $corruptedCount,
                'pending' => $pendingCount,
            ],
            'summary' => [
                'avg_duration' => round((float) BackupHistory::where('duration_seconds', '>', 0)->avg('duration_seconds') ?: 0, 2),
                'total_restores' => BackupRestoreHistory::count(),
                'success_restores' => BackupRestoreHistory::where('status', 'Success')->count(),
            ],
        ]);
    }

    /**
     * General Statistics API
     */
    public function statistics()
    {
        return response()->json([
            'total' => BackupHistory::count(),
            'today' => BackupHistory::whereDate('created_at', Carbon::today())->count(),
            'success' => BackupHistory::where('status', 'Success')->count(),
            'failed' => BackupHistory::where('status', 'Failed')->count(),
            'avg_duration' => round((float) BackupHistory::where('duration_seconds', '>', 0)->avg('duration_seconds') ?: 0, 2),
        ]);
    }
}
