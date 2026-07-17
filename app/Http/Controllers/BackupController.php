<?php

namespace App\Http\Controllers;

use App\Models\BackupHistory;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Carbon\Carbon;

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
            'file_name' => $fileName,
            'file_size' => round($file->getSize() / 1024, 2) . ' KB',
            'status' => 'Success',
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
}
