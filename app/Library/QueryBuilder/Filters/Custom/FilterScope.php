<?php

namespace App\Library\QueryBuilder\Filters\Custom;

use App\Library\QueryBuilder\Filters\Filter;
use App\Library\QueryBuilder\Filters\FilterParams;

class FilterScope implements Filter
{
    protected $scope;
    public function __construct(string $scope)
    {
        $this->scope = $scope;
    }
    public function __invoke($query, FilterParams $params, string $property)
    {
        // local or global scope cua laravel
        $query->{$this->scope}($property, 'like', $params->getValue());
    }
}
