<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Task extends Model
{
    use HasFactory;
    protected $table = 'tasks';
    protected $fillable = [
        'name',
        'status',
        'start_at',
        'end_at',
        'error',
        'queue_name',
        'task_type',
    ];
    protected $casts = [
        'start_at' => 'datetime',
        'end_at' => 'datetime',
    ];
    public function uploadedFile()
    {
        return $this->hasOne(UploadedFile::class);
    }
}
