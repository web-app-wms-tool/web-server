<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ConvertedFile extends Model
{
    use HasFactory;
    protected $table = 'converted_files';
    protected $fillable = [
        'name',
        'path',
        'task_id',
    ];
    public function task()
    {
        return $this->belongsTo(Task::class);
    }
}
