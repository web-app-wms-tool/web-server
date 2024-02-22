<?php

namespace App\Traits;

use Illuminate\Support\Str;


trait Uuid
{
    public static function generateUuid()
    {
        return Str::uuid();
    }

    protected static function bootUuid()
    {
        static::creating(function ($model) {
            if ($model->getKey() === null) {
                $model->setAttribute($model->getKeyName(), Str::uuid()->toString());
            }
        });
    }

    // Tells the database not to auto-increment this field
    public function getIncrementing()
    {
        return false;
    }

    // Helps the application specify the field type in the database
    public function getKeyType()
    {
        return 'string';
    }
}
