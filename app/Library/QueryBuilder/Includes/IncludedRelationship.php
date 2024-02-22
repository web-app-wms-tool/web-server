<?php

namespace App\Library\QueryBuilder\Includes;

use Closure;
use Illuminate\Support\Collection;

class IncludedRelationship implements IncludeInterface
{
    public function __invoke($query, string $relationship)
    {
        $relatedTables = collect(explode('.', $relationship));

        $withs = $relatedTables
            ->mapWithKeys(function ($table, $key) use ($query, $relatedTables) {
                $fullRelationName = $relatedTables->slice(0, $key + 1)->implode('.');
                return [$fullRelationName];
            })
            ->toArray();

        $query->with($withs);
    }

    public static function getIndividualRelationshipPathsFromInclude(string $include): Collection
    {
        return collect(explode('.', $include))
            ->reduce(function (Collection $includes, string $relationship) {
                if ($includes->isEmpty()) {
                    return $includes->push($relationship);
                }

                return $includes->push("{$includes->last()}.{$relationship}");
            }, collect());
    }
}
