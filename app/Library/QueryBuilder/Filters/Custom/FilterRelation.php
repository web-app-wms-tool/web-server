<?php

namespace App\Library\QueryBuilder\Filters\Custom;

use App\Library\QueryBuilder\Filters\Filter;
use App\Library\QueryBuilder\Filters\FilterParams;

class FilterRelation implements Filter
{
    protected $relation;
    protected $field;
    protected $table;
    public function __construct(string $relation, string $field = 'id', ?string $table = null, $withoutTable = false)
    {
        $this->relation = $relation;
        $this->field = $field;
        $this->table = $withoutTable ? null : ($table ??  $relation);
    }
    public function __invoke($query, FilterParams $params, string $property)
    {
        $query->whereHas($this->relation, function ($query) use ($params) {
            if (is_array($params->getValue())) {
                $query->whereIn(!empty($this->table) ? $this->table . "." . $this->field : $this->field, $params->getValue());
            } else {
                $query->where(!empty($this->table) ? $this->table . "." . $this->field : $this->field, $params->getValue());
            }
        });
    }
}
