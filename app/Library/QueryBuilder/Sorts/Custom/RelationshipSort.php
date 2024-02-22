<?php

namespace App\Library\QueryBuilder\Sorts\Custom;

use App\Library\QueryBuilder\Sorts\Sort;

class RelationshipSort implements Sort
{
    protected $table = '';
    protected $field = '';
    protected $foreign_key = '';
    protected $owner_key = '';
    public function __construct(string $table, string $field, string $foreign_key, string $owner_key = 'id')
    {
        $this->table = $table;
        $this->field = $field;
        $this->foreign_key = $foreign_key;
        $this->owner_key = $owner_key;
    }
    public function __invoke($query, bool $descending, string $property)
    {
        $tableAlias = \Str::random(8);
        $property = $tableAlias . '.' . $this->field;
        $query_table = $query->getModel()->getTable();
        $query->leftJoin(
            $this->table . ' as ' . $tableAlias,
            $query_table . '.' . $this->foreign_key,
            '=',
            $tableAlias . '.' . $this->owner_key
        )->select($query_table . '.*');

        $query->orderBy($property, $descending ? 'desc' : 'asc');
    }
}
