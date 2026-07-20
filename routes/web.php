<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\BackupController;

Route::get('/', function () {
    return redirect()->route('backup.index');
});

Route::get('/backups', [BackupController::class, 'index'])->name('backup.index');

Route::get('/backups/create', [BackupController::class, 'create'])->name('backup.create');

Route::post('/backups/store', [BackupController::class, 'store'])->name('backup.store');

Route::get('/backups/download/{id}', [BackupController::class, 'download'])->name('backup.download');

Route::delete('/backups/delete/{id}', [BackupController::class, 'destroy'])->name('backup.delete');

/*
|--------------------------------------------------------------------------
| Backup Verification
|--------------------------------------------------------------------------
*/

Route::post('/backups/{id}/verify', [BackupController::class, 'verify'])
    ->name('backup.verify');

Route::get('/backups/{id}/verification-history', [BackupController::class, 'verificationHistory'])
    ->name('backup.verification.history');

Route::post('/backups/cleanup', [BackupController::class, 'cleanup'])
    ->name('backup.cleanup');

Route::get('/backups/cleanup-history', [BackupController::class, 'cleanupHistory'])
    ->name('backup.cleanup.history');
    
Route::get('/statistics', [BackupController::class, 'statistics'])->name('backup.statistics');
