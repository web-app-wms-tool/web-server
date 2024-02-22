<?php

namespace App\Library\QueryBuilder\Filters\Custom;

use App\Library\QueryBuilder\Filters\Filter;
use App\Library\QueryBuilder\Filters\FilterParams;

class FilterMulti implements Filter
{
    public function __invoke($query, FilterParams $params, string $property)
    {
        $value = is_array($params->getValue()) ? $params->getValue() : explode(",", $params->getValue());
        $query->whereIn($property, $value);
    }
}
