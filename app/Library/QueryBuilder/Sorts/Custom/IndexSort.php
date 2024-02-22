<?php

namespace App\Library\QueryBuilder\Sorts\Custom;

use App\Library\QueryBuilder\Sorts\Sort;

class IndexSort implements Sort
{
    public function __invoke($query, bool $descending, string $property)
    {
        if ($descending) {
            $query->orderByRaw($property . ' desc,' . $property . '=0');
        } else {
            $query->orderByRaw($property . '=0, ' . $property . ' asc');
        }
    }
}
