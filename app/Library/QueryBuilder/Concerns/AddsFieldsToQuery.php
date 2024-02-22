<?php

namespace App\Library\QueryBuilder\Concerns;

use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use App\Library\QueryBuilder\Exceptions\InvalidFieldQuery;

trait AddsFieldsToQuery
{
    public function allowedFields($fields): self
    {
        if ($this->request->fields()->isEmpty()) {
            return $this;
        }
        $fields = is_array($fields) ? $fields : func_get_args();
        $this->allowedFields = $this->allowedFields->concat(collect($fields)
            ->map(function (string $fieldName) {
                return $this->prependField($fieldName);
            }));

        $this->addFunctionToCallWhenGet(function () {
            $this->ensureAllFieldsExist();
            $this->addRequestedModelFieldsToQuery();
        });

        return $this;
    }

    protected function addRequestedModelFieldsToQuery()
    {
        $modelTableName = $this->getModel()->getTable();

        $modelFields = $this->request->fields();
        $prependedFields = $this->prependFieldsWithTableName($modelFields, $modelTableName);
        $this->select($prependedFields->toArray());
    }

    protected function ensureAllFieldsExist()
    {
        $modelTableName = $this->getModel()->getTable();

        $requestedFields = $this->prependFieldsWithTableName($this->request->fields())->unique();

        $unknownFields = $requestedFields->diff($this->allowedFields);

        if ($unknownFields->isNotEmpty()) {
            throw InvalidFieldQuery::fieldsNotAllowed($unknownFields->map(function ($item) use ($modelTableName) {
                return str_replace($modelTableName . ".", "", $item);
            }), $this->allowedFields->map(function ($item) use ($modelTableName) {
                return str_replace($modelTableName . ".", "", $item);
            }));
        }
    }

    protected function prependFieldsWithTableName(Collection $fields, string $tableName = ''): Collection
    {
        return  $fields->map(function ($field) use ($tableName) {
            return $this->prependField($field, $tableName);
        });
    }

    protected function prependField(string $field, ?string $table = null): string
    {
        if (!$table) {
            $table = $this->getModel()->getTable();
        }

        if (Str::contains($field, '.')) {
            // Already prepended

            return $field;
        }

        return "{$table}.{$field}";
    }
}
