<?php

namespace App\Library\QueryBuilder\Filters;

use Illuminate\Support\Str;

class FiltersPartial implements Filter
{
    const DEFAULT_NEGATIVE_CHAR = '!';


    public function __invoke($query, FilterParams $params, string $property)
    {
        if ($this->isNegative($params->getValue($params->getValue()))) {
            $query->where($property, '!=', $this->sanitise($params->getValue()));
        } else {
            $query->where($property, $params->getValue());
        }
    }

    protected function isNegative(string $value): bool
    {
        return Str::startsWith($value, self::DEFAULT_NEGATIVE_CHAR);
    }
    protected function sanitise(string $value): string
    {
        return Str::after($value, self::DEFAULT_NEGATIVE_CHAR);
    }
}
