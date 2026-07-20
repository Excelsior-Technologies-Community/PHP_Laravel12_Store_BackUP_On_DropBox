<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class BackupVerificationHistory extends Model
{
    use HasFactory;

    protected $fillable = [
        'backup_history_id',
        'verification_status',
        'remarks',
        'verified_at',
    ];

    protected $casts = [
        'verified_at' => 'datetime',
    ];

    public function backup()
    {
        return $this->belongsTo(
            BackupHistory::class,
            'backup_history_id'
        );
    }
}