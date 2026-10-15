<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\BackupController;

/*
|--------------------------------------------------------------------------
| Web Routes - Dropbox Cloud Backup & Disaster Recovery System
|--------------------------------------------------------------------------
*/

Route::get('/', function () {
    return redirect()->route('backup.index');
});

// Main Dashboard & Backup Management
Route::get('/backups', [BackupController::class, 'index'])->name('backup.index');
Route::get('/backups/create', [BackupController::class, 'create'])->name('backup.create');
Route::post('/backups/store', [BackupController::class, 'store'])->name('backup.store');
Route::get('/backups/download/{id}', [BackupController::class, 'download'])->name('backup.download');
Route::delete('/backups/delete/{id}', [BackupController::class, 'destroy'])->name('backup.delete');

// Dropbox Cloud Operations & Quota
Route::post('/backups/{id}/sync-dropbox', [BackupController::class, 'syncToDropbox'])->name('backup.sync.dropbox');
Route::post('/backups/{id}/sync', [BackupController::class, 'syncToDropbox'])->name('backup.sync');
Route::get('/backups/{id}/share-link', [BackupController::class, 'shareLink'])->name('backup.share');
Route::get('/backups/dropbox/status', [BackupController::class, 'testDropboxConnection'])->name('backup.dropbox.status');
Route::get('/backups/dropbox/test', [BackupController::class, 'testDropboxConnection'])->name('backup.dropbox.test');
Route::get('/backups/dropbox/quota', [BackupController::class, 'getDropboxQuota'])->name('backup.dropbox.quota');

// Backup Analytics & 30-day Chart API
Route::get('/backups/analytics', [BackupController::class, 'analytics'])->name('backup.analytics');
Route::get('/statistics', [BackupController::class, 'statistics'])->name('backup.statistics');

// One-Click Database Restore & History
Route::post('/backups/{id}/restore', [BackupController::class, 'restore'])->name('backup.restore');
Route::get('/backups/restore-history', [BackupController::class, 'restoreHistory'])->name('backup.restore.history');

// Integrity Verification & Verification History
Route::post('/backups/{id}/verify', [BackupController::class, 'verify'])->name('backup.verify');
Route::get('/backups/{id}/verification-history', [BackupController::class, 'verificationHistory'])->name('backup.verification.history');

// Retention Cleanup & History
Route::post('/backups/cleanup', [BackupController::class, 'cleanup'])->name('backup.cleanup');
Route::get('/backups/cleanup-history', [BackupController::class, 'cleanupHistory'])->name('backup.cleanup.history');
