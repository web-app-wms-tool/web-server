<?php

namespace App\Library\QueryBuilder\Sorts;


class SortsField implements Sort
{
    public function __invoke($query, bool $descending, string $property)
    {
        $query->orderBy($property, $descending ? 'desc' : 'asc');
    }
}
