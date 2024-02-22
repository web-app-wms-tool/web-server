<?php

namespace App\Library\QueryBuilder\Includes;


interface IncludeInterface
{
    public function __invoke($query, string $include);
}
