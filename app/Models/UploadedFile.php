<?php

namespace App\Models;

use App\Helpers\FileHelper;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class UploadedFile extends Model
{
    use HasFactory;
    protected $table = "uploaded_files";

    protected $fillable = [
        'name',
        'path',
        'dxf_path',
        'uuid',
        'size',
        'task_id',
        'is_read_done',
        'metadata',
        'srs',
    ];
    protected $casts = [
        'metadata' => 'array',
    ];
    public function getSizeAttribute()
    {
        if (empty($this->attributes['size'])) {
            return '';
        }

        return FileHelper::humanFileSize($this->attributes['size']);
    }
    public function task()
    {
        return $this->belongsTo(Task::class);
    }
}
