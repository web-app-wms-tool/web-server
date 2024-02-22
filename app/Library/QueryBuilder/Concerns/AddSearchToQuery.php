<?php

namespace App\Library\QueryBuilder\Concerns;

use Illuminate\Support\Str;


trait AddSearchToQuery
{
    public function allowedSearch($fields): self
    {
        if (empty($this->request->search()) || count($fields) < 1) {
            return $this;
        }

        $this->addFunctionToCallWhenGet(function () use ($fields) {
            $this->addSearchValueToQuery($fields, $this->request->search());
        });

        return $this;
    }

    protected function addSearchValueToQuery($fields, $value)
    {
        $modelTableName = $this->getModel()->getTable();
        $prependedFields = $this->prependFieldsWithTableName(collect($fields), $modelTableName);
        $this->where(function ($q) use ($prependedFields, $value, $modelTableName) {
            $prependedFields->each(function ($field) use ($q, $value, $modelTableName) {
                $value = trim($value);
                $value = str_replace(["%", "_", "^", "@"], ["\%", "\_", "\^", "\@"], $value);
                $q->when(
                    strpos($field, '.') !== false && !Str::startsWith($field, $modelTableName . '.'),
                    function ($query) use ($field, $value) {
                        [$relationName, $relationAttribute] = explode('.', $field);

                        $query->orWhereHas($relationName, function ($query) use ($relationAttribute, $value) {
                            $query->where($relationAttribute, 'ilike', "%{$value}%");
                        });
                    },
                    function ($query) use ($field, $value) {
                        $query->orWhere($field, 'ilike', "%{$value}%");
                    }
                );
            });
        });
    }
}
