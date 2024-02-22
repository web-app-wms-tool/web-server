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
    protected $appends = [
        'status_full_text',
    ];
    public function getStatusFullTextAttribute()
    {
        switch ($this->status) {
            case 0:
                return 'Created';
            case 1:
                return 'Processing';
            case 2:
                return 'Completed';
            case 3:
                return 'Failed';
            default:
                return;
        }
    }
    public function convertedFile()
    {
        return $this->hasOne(ConvertedFile::class);
    }
}
