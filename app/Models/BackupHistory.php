<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class BackupHistory extends Model
{
    use HasFactory;

    protected $table = 'backup_histories';

    protected $fillable = [
        'file_name',
        'file_size',
        'status',
        'storage_disk',
        'dropbox_path',
        'duration_seconds',
        'checksum_sha256',
        'verification_status',
        'verified_at',
    ];

    protected $casts = [
        'verified_at' => 'datetime',
        'duration_seconds' => 'float',
    ];

    public function verificationHistories()
    {
        return $this->hasMany(
            BackupVerificationHistory::class,
            'backup_history_id'
        )->latest();
    }

    public function restoreHistories()
    {
        return $this->hasMany(
            BackupRestoreHistory::class,
            'backup_history_id'
        )->latest();
    }

    public function isAvailableOnDropbox(): bool
    {
        return in_array($this->storage_disk, ['dropbox', 'both']);
    }

    public function isAvailableLocally(): bool
    {
        return in_array($this->storage_disk, ['local', 'both']);
    }
}