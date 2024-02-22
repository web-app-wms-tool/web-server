<?php

namespace App\Library\QueryBuilder\Filters\Custom;

use App\Library\QueryBuilder\Filters\Filter;
use App\Library\QueryBuilder\Filters\FilterParams;
use Illuminate\Support\Facades\DB;

class FilterInJsonArray implements Filter
{
    public function __invoke($query, FilterParams $params, string $property)
    {
        $query->whereRaw(DB::raw($property . "::jsonb @> '[" . $params->getValue() . "]'"));
    }
}
