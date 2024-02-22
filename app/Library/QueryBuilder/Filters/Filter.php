<?php

namespace App\Library\QueryBuilder\Filters;


interface Filter
{
    public function __invoke($query, FilterParams $value, string $property);
}
