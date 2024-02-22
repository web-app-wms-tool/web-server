<?php

namespace App\Library\QueryBuilder\Sorts\Custom;

use App\Library\QueryBuilder\Sorts\Sort;

class TranslationJsonSort implements Sort
{
    public function __invoke($query, bool $descending, string $property)
    {
        $query->orderBy($property . '->' . app()->getLocale(), $descending ? 'desc' : 'asc');
    }
}
