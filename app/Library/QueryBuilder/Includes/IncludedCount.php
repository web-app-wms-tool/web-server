<?php

namespace App\Library\QueryBuilder\Includes;

use Illuminate\Support\Str;

class IncludedCount implements IncludeInterface
{
    public function __invoke($query, string $count)
    {
        $query->withCount(Str::before($count, config('query-builder.count_suffix')));
    }
}
