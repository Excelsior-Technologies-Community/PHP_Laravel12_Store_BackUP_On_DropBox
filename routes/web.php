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

Route::get('/statistics', [BackupController::class, 'statistics'])->name('backup.statistics');
