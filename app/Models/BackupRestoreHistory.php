<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class BackupRestoreHistory extends Model
{
    use HasFactory;

    protected $table = 'backup_restore_histories';

    protected $fillable = [
        'backup_history_id',
        'file_name',
        'status',
        'duration_seconds',
        'remarks',
        'restored_at',
    ];

    protected $casts = [
        'restored_at' => 'datetime',
        'duration_seconds' => 'float',
    ];

    public function backup()
    {
        return $this->belongsTo(BackupHistory::class, 'backup_history_id');
    }
}
