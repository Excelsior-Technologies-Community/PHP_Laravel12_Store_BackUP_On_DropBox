<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class BackupCleanupHistory extends Model
{
    use HasFactory;

    protected $table = 'backup_cleanup_histories';

    protected $fillable = [
        'retention_limit',
        'deleted_backups',
        'freed_space',
        'status',
        'remarks',
        'cleaned_at',
    ];

    protected $casts = [
        'cleaned_at' => 'datetime',
    ];
}