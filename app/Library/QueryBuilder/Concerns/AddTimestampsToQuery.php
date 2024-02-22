<?php

namespace App\Library\QueryBuilder\Concerns;

use App\Library\QueryBuilder\Filters\AllowedFilter;
use App\Library\QueryBuilder\Sorts\AllowedSort;

trait AddTimestampsToQuery
{
    public function allowedTimestamps(): self
    {
        if (!$this->request->sorts()->isEmpty()) {
            $this->allowedSorts = $this->allowedSorts->concat([
                AllowedSort::field('created_at'),
                AllowedSort::field('updated_at')
            ]);
        }
        if (!$this->request->filters()->isEmpty()) {
            $this->allowedFilters =  $this->allowedFilters->concat([
                AllowedFilter::field('created_at'),
                AllowedFilter::field('updated_at')
            ]);
        }
        if (!$this->request->fields()->isEmpty()) {
            $this->allowedFields =  $this->allowedFields->concat([
                $this->prependField('created_at'),
                $this->prependField('updated_at')
            ]);
        }
        return $this;
    }
}
