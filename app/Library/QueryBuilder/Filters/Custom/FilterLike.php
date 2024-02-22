<?php

namespace App\Library\QueryBuilder\Filters\Custom;

use App\Library\QueryBuilder\Filters\Filter;
use App\Library\QueryBuilder\Filters\FilterParams;

class FilterLike implements Filter
{
    public function __invoke($query, FilterParams $params, string $property)
    {

        $query->where($property, 'like', $params->getValue());
    }
}
