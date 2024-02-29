<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ConvertedLayer extends Model
{
    use HasFactory;
    protected $table = 'converted_layers';
    protected $fillable = [
        'layer_name',
        'geoserver_ref',
        'srs',
        'uuid',
        'task_id',
        'metadata',
    ];
    protected $casts = [
        'metadata' => 'array',
    ];
    public function task()
    {
        return $this->belongsTo(Task::class);
    }
}
