<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Srs extends Model
{
    use HasFactory;
    protected $table = 'srs';
    protected $fillable = [
        'name',
        'code',
        'description',
    ];
}
