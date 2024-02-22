<?php

namespace App\Library\QueryBuilder\Filters\Custom;

use App\Library\QueryBuilder\Filters\Filter;
use App\Library\QueryBuilder\Filters\FilterParams;
use Carbon\Carbon;

class FilterGeometry implements Filter
{
    public function __invoke($query, FilterParams $params, string $property)
    {
        $geometry = $params->getValue();
        if (empty($geometry)) return;
        if (!is_string($geometry)) {
            $geometry = json_encode($geometry);
        }
        $query->whereRaw('ST_Contains(ST_GeomFromGeoJSON(?)::geometry,geometry::geometry)', [$geometry]);
    }
}
