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
    ];
}
