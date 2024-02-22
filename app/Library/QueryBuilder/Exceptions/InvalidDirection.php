<?php

namespace App\Library\QueryBuilder\Exceptions;

use Exception;
use App\Library\QueryBuilder\Sorts\SortDirection;

class InvalidDirection extends Exception
{
    public static function make(string $sort)
    {
        return new static('The direction should be either `' . SortDirection::DESCENDING . '` or `' . SortDirection::ASCENDING) . "`. ${sort} given.";
    }
}
