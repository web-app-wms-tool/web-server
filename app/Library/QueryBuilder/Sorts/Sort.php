<?php

namespace App\Library\QueryBuilder\Sorts;


interface Sort
{
    public function __invoke($query, bool $descending, string $property);
}
