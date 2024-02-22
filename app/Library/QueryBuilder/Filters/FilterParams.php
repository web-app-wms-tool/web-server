<?php

namespace App\Library\QueryBuilder\Filters;

class FilterParams
{
    public $field;
    public $value;
    public function __construct(string $field, $value)
    {
        $this->field = $field;
        $this->value = $value;
    }
    public function getField()
    {
        return $this->field;
    }
    public function setField($field)
    {
        $this->field = $field;
    }
    public function getValue()
    {
        return $this->value;
    }
    public function setValue($value)
    {
        $this->value = $value;
    }
}
