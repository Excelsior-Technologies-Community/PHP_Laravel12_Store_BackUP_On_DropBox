<?php

namespace App\Http\Controllers;

use App\Models\BackupHistory;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Carbon\Carbon;
use App\Models\BackupVerificationHistory;
use ZipArchive;
use App\Models\BackupCleanupHistory;

class BackupController extends Controller
{
    public function index(Request $request)
    {
        $search = $request->search;

        $backups = BackupHistory::when($search, function ($query) use ($search) {
            $query->where('file_name', 'like', "%{$search}%");
        })
            ->oldest()
            ->paginate(5);

        $totalBackups = BackupHistory::count();
        $todayBackups = BackupHistory::whereDate('created_at', Carbon::today())->count();
        $successBackups = BackupHistory::where('status', 'Success')->count();
        $failedBackups = BackupHistory::where('status', 'Failed')->count();

        return view('backup.index', compact(
            'backups',
            'search',
            'totalBackups',
            'todayBackups',
            'successBackups',
            'failedBackups'
        ));
    }

    public function create()
    {
        return view('backup.create');
    }

    public function store(Request $request)
    {
        $request->validate([
            'backup_file' => 'required|file|mimes:zip',
        ]);

        $file = $request->file('backup_file');

        $fileName = time() . '_' . $file->getClientOriginalName();

        Storage::disk('public')->putFileAs(
            'backups',
            $file,
            $fileName
        );

        BackupHistory::create([
            'file_name'             => $fileName,
            'file_size'             => round($file->getSize() / 1024, 2) . ' KB',
            'status'                => 'Success',
            'verification_status'   => 'Pending',
            'verified_at'           => null,
        ]);

        return redirect()->route('backup.index')
            ->with('success', 'Backup uploaded successfully.');
    }

    public function download($id)
    {
        $backup = BackupHistory::findOrFail($id);

        return Storage::disk('public')->download(
            'backups/' . $backup->file_name
        );
    }

    public function destroy($id)
    {
        $backup = BackupHistory::findOrFail($id);

        if (Storage::disk('public')->exists('backups/' . $backup->file_name)) {
            Storage::disk('public')->delete('backups/' . $backup->file_name);
        }

        $backup->delete();

        return redirect()->back()
            ->with('success', 'Backup deleted successfully.');
    }

    public function statistics()
    {
        return response()->json([
            'total' => BackupHistory::count(),
            'today' => BackupHistory::whereDate('created_at', Carbon::today())->count(),
            'success' => BackupHistory::where('status', 'Success')->count(),
            'failed' => BackupHistory::where('status', 'Failed')->count(),
        ]);
    }

    public function verify($id)
    {
        $backup = BackupHistory::findOrFail($id);

        $path = storage_path('app/public/backups/' . $backup->file_name);

        $status = 'Pending';
        $remarks = '';

        // Check if backup file exists
        if (!file_exists($path)) {

            $status = 'Missing';
            $remarks = 'Backup file does not exist.';
        } else {

            $zip = new ZipArchive();

            if ($zip->open($path) === true) {

                $zip->close();

                $status = 'Verified';
                $remarks = 'ZIP file verified successfully.';
            } else {

                $status = 'Corrupted';
                $remarks = 'ZIP archive is corrupted or unreadable.';
            }
        }

        // Update current verification status
        $backup->update([
            'verification_status' => $status,
            'verified_at' => now(),
        ]);

        // Save verification history
        BackupVerificationHistory::create([
            'backup_history_id' => $backup->id,
            'verification_status' => $status,
            'remarks' => $remarks,
            'verified_at' => now(),
        ]);

        return redirect()
            ->route('backup.index')
            ->with('success', 'Backup verification completed successfully.');
    }

    public function verificationHistory($id)
    {
        $backup = BackupHistory::findOrFail($id);

        $histories = $backup
            ->verificationHistories()
            ->paginate(10);

        return view(
            'backup.verification-history',
            compact(
                'backup',
                'histories'
            )
        );
    }

    public function cleanup(Request $request)
    {
        $request->validate([
            'retention_limit' => 'required|integer|min:1',
        ]);

        $retentionLimit = (int) $request->retention_limit;

        $backups = BackupHistory::latest()->get();

        $deleteBackups = $backups->slice($retentionLimit);

        // No old backups available
        if ($deleteBackups->isEmpty()) {

            BackupCleanupHistory::create([
                'retention_limit' => $retentionLimit,
                'deleted_backups' => 0,
                'freed_space' => '0 MB',
                'status' => 'Success',
                'remarks' => 'No cleanup required. Backup count is already within retention limit.',
                'cleaned_at' => now(),
            ]);

            return redirect()
                ->back()
                ->with('info', 'No cleanup required. Backup count is already within retention limit.');
        }

        $deletedCount = 0;
        $freedSpace = 0;

        foreach ($deleteBackups as $backup) {

            $filePath = 'backups/' . $backup->file_name;

            if (Storage::disk('public')->exists($filePath)) {

                $freedSpace += Storage::disk('public')->size($filePath);

                Storage::disk('public')->delete($filePath);
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

            'remarks' => 'Old backups removed successfully.',

            'cleaned_at' => now(),

        ]);

        return redirect()
            ->back()
            ->with('success', 'Backup cleanup completed successfully.');
    }
    public function cleanupHistory()
    {
        $histories = BackupCleanupHistory::latest()
            ->paginate(10);

        return view(
            'backup.cleanup-history',
            compact('histories')
        );
    }
}
