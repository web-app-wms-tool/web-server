<?php

namespace App\Library\QueryBuilder\Concerns;

use App\Library\QueryBuilder\Filters\AllowedFilter;

trait AddFilterToQuery
{
    public function allowedFilters($filters): self
    {
        if ($this->request->filters()->isEmpty()) {
            return $this;
        }
        $filters = is_array($filters) ? $filters : func_get_args();
        $this->allowedFilters = $this->allowedFilters->concat(collect($filters)->map(function ($filter) {
            if ($filter instanceof AllowedFilter) {
                return $filter;
            }
            return AllowedFilter::field($filter);
        }));
        $this->addFunctionToCallWhenGet(function () {
            $this->addFiltersToQuery($this->request->filters());
        });
        return $this;
    }
    public function addFiltersToQuery($filters)
    {
        collect($filters)->each(function ($query) {
            $filter = $this->allowedFilters->first(function (AllowedFilter $value) use ($query) {
                return  $value->checkFilterCan($query->getField());
            });
            if (isset($filter)) {
                $filter->filter($this, $query);
            }
        });
    }
}
