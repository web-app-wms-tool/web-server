<?php

namespace App\Library\QueryBuilder\Filters\Custom;

use App\Library\QueryBuilder\Filters\Filter;
use App\Library\QueryBuilder\Filters\FilterParams;
use Carbon\Carbon;

class FilterDateType implements Filter
{
    protected $operator;
    protected $format;
    public function __construct($operator, callable $format = null)
    {
        $this->operator = $operator;
        $this->format = $format ?? function ($value) {
            return $value;
        };
    }
    public function __invoke($query, FilterParams $params, string $property)
    {

        $value = $params->getValue();
        $format =  $this->format;
        if (!empty($value)) {
            $value = Carbon::createFromFormat(config('app.format_date'), $value);
            $value = $format($value);
            $query->where($property, $this->operator, $value);
        }
    }
}
