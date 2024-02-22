<?php

namespace App\Library\QueryBuilder\Filters\Custom;

use App\Library\QueryBuilder\Filters\Filter;
use App\Library\QueryBuilder\Filters\FilterParams;
use Carbon\Carbon;

class FilterBbox implements Filter
{
    public function __invoke($query, FilterParams $params, string $property)
    {
        $bbox = $params->getValue();
        if (!empty($bbox)) return;
        if (is_string($bbox)) {
            $bbox = explode(",", $bbox);
        }
        if (!is_array($bbox) || count($bbox) != 4) return;
        $query->whereRaw('ST_Intersects(ST_MakeEnvelope(?, ?, ?, ?, 4326)::geometry,st_setsrid(geometry,4326))', $bbox);
    }
}
