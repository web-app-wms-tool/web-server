<?php

namespace App\Library\QueryBuilder\Concerns;

use App\Library\QueryBuilder\Sorts\AllowedSort;
use App\Library\QueryBuilder\Exceptions\InvalidSortQuery;

trait AddSortToQuery
{
    protected $default_sort;
    protected function checkSorts($sorts)
    {
        return collect($sorts)->map(function ($sort) {
            if ($sort instanceof AllowedSort) {
                return $sort;
            }

            return AllowedSort::field($sort);
        });
    }
    public function allowedSorts($sorts): self
    {
        if ($this->request->sorts()->isEmpty()) {
            return $this;
        }
        $sorts = is_array($sorts) ? $sorts : func_get_args();
        $this->allowedSorts = $this->allowedSorts->concat($this->checkSorts($sorts));

        $this->addFunctionToCallWhenGet(function () {
            $this->ensureAllSortsExist();
            $this->addSortsToQuery($this->request->sorts());
        });
        return $this;
    }
    public function defaultSort($sorts): self
    {
        if (empty($sorts)) {
            return $this;
        }
        return $this->defaultSorts(func_get_args());
    }
    public function defaultSorts($sorts): self
    {
        if (empty($this->default_sort)) {
            $this->addFunctionToCallWhenGet(
                function () {
                    if (!$this->request->sorts()->isEmpty()) {
                        // We've got requested sorts. No need to parse defaults.
                        return $this;
                    }
                    if ($this->is_use_aggrid && count($this->request->aggrid()['sortModel']) > 0) {
                        // We've got requested sorts. No need to parse defaults.
                        return $this;
                    }
                    $sorts = is_array($this->default_sort) ? $this->default_sort : func_get_args();
                    $this->checkSorts($sorts)
                        ->each(function (AllowedSort $sort) {
                            $sort->sort($this);
                        });
                }
            );
        }

        $this->default_sort = $sorts;
        return $this;
    }

    protected function addSortsToQuery($sorts)
    {
        collect($sorts)
            ->each(function (string $property) {
                $descending = $property[0] === '-';

                $key = ltrim($property, '-');

                $sort = $this->findSort($key);

                $sort->sort($this, $descending);
            });
    }

    protected function findSort(string $property): ?AllowedSort
    {
        return $this->allowedSorts
            ->first(function (AllowedSort $sort) use ($property) {
                return $sort->isSort($property);
            });
    }

    protected function ensureAllSortsExist(): void
    {
        $requestedSortNames = $this->request->sorts()->map(function (string $sort) {
            return ltrim($sort, '-');
        });

        $allowedSortNames = $this->allowedSorts->map(function (AllowedSort $sort) {
            return $sort->getName();
        });

        $unknownSorts = $requestedSortNames->diff($allowedSortNames);

        if ($unknownSorts->isNotEmpty()) {
            throw InvalidSortQuery::sortsNotAllowed($unknownSorts, $allowedSortNames);
        }
    }
}
