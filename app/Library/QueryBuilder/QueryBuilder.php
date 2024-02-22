<?php

namespace App\Library\QueryBuilder;

use App\Library\QueryBuilder\Concerns\AddAgGridToQuery;
use App\Library\QueryBuilder\Concerns\AddFilterToQuery;
use App\Library\QueryBuilder\Concerns\AddPaginationToQuery;
use App\Library\QueryBuilder\Concerns\AddSearchToQuery;
use App\Library\QueryBuilder\Concerns\AddsFieldsToQuery;
use App\Library\QueryBuilder\Concerns\AddsIncludesToQuery;
use App\Library\QueryBuilder\Concerns\AddSortToQuery;
use App\Library\QueryBuilder\Concerns\AddTimestampsToQuery;
use App\Library\QueryBuilder\Concerns\AppendsAttributesToResults;
use Illuminate\Database\Eloquent\Builder as EloquentBuilder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Illuminate\Support\Traits\ForwardsCalls;

class QueryBuilder
{
    use AppendsAttributesToResults,
        AddSortToQuery,
        AddFilterToQuery,
        AddPaginationToQuery,
        AddsIncludesToQuery,
        AddsFieldsToQuery,
        AddTimestampsToQuery,
        AddSearchToQuery,
        AddAgGridToQuery,
        ForwardsCalls;

    /** @var \Illuminate\Support\Collection */
    protected $allowedSorts;

    /** @var \Illuminate\Support\Collection */
    protected $allowedFilters;

    /** @var \Illuminate\Support\Collection */
    protected $allowedFields;

    /** @var \Illuminate\Support\Collection */
    protected $funcCalls;

    /** @var \App\Library\QueryBuilder\QueryBuilderRequest */
    protected $request;

    /** @var \Illuminate\Database\Eloquent\Builder|\Illuminate\Database\Eloquent\Relations\Relation|\Staudenmeir\LaravelCte\Query\Builder */
    protected $subject;

    protected $func_get;
    protected $is_use_aggrid = false;

    /**
     * @param \Illuminate\Database\Eloquent\Builder|\Illuminate\Database\Eloquent\Relations\Relation|\Staudenmeir\LaravelCte\Query\Builder $subject
     * @param null|\Illuminate\Http\Request $request
     */
    public function __construct($subject, ?Request $request = null)
    {
        $this->funcCalls = collect([]);
        $this->allowedSorts = collect([]);
        $this->allowedFilters = collect([]);
        $this->allowedFields = collect(["*"]);
        $this->initializeSubject($subject)
            ->initializeRequest($request ?? app(Request::class));

        if (method_exists($this->subject, 'getModel')) {
            if ($this->getModel()->usesTimestamps()) {
                $this->allowedTimestamps();
                $this->defaultSort('-' . $this->getModel()->getKeyName());
            }
        }
    }

    /**
     * @param \Illuminate\Database\Eloquent\Builder|\Illuminate\Database\Eloquent\Relations\Relation|\Staudenmeir\LaravelCte\Query\Builder $subject
     *
     * @return $this
     */
    protected function initializeSubject($subject): self
    {
        $this->subject = $subject;

        return $this;
    }

    protected function initializeRequest(?Request $request = null): self
    {
        $this->request = $request
            ? QueryBuilderRequest::fromRequest($request)
            : app(QueryBuilderRequest::class);

        return $this;
    }

    public function getEloquentBuilder()
    {
        if ($this->subject instanceof EloquentBuilder) {
            return $this->subject;
        }

        if ($this->subject instanceof Relation) {
            return $this->subject->getQuery();
        }
        return $this->subject;
    }

    public function getSubject()
    {
        return $this->subject;
    }

    /**
     * @param \Illuminate\Database\Query\Builder|EloquentBuilder|Relation|string $subject
     * @param Request|null $request
     *
     * @return static
     */
    public static function for($subject, ?Request $request = null): self
    {
        if (is_subclass_of($subject, Model::class)) {
            $subject = $subject::query();
        }

        return new static($subject, $request);
    }
    public function __call($name, $arguments)
    {
        $result = $this->forwardCallTo($this->subject, $name, $arguments);

        /*
         * If the forwarded method call is part of a chain we can return $this
         * instead of the actual $result to keep the chain going.
         */
        if ($result === $this->subject) {
            return $this;
        }
        if ($result instanceof Model) {
            $this->addAppendsToResults(collect([$result]));
        }

        if ($result instanceof Collection) {
            $this->addAppendsToResults($result);
        }



        return $result;
    }
    protected function addFunctionToCallWhenGet($fun)
    {
        $this->funcCalls = $this->funcCalls->concat([$fun]);
    }
    public function get()
    {
        if (is_callable($this->func_get)) {
            return call_user_func_array($this->func_get, func_get_args());
        }
        return $this->callAllAddToQuery()->__call('get', func_get_args());
    }
    public function findOrFail()
    {
        if (is_callable($this->func_get)) {
            return call_user_func_array($this->func_get, func_get_args());
        }
        return $this->callAllAddToQuery()->__call('findOrFail', func_get_args());
    }
    public function find()
    {
        if (is_callable($this->func_get)) {
            return call_user_func_array($this->func_get, func_get_args());
        }
        return $this->callAllAddToQuery()->__call('find', func_get_args());
    }
    public function paginate()
    {
        return $this->callAllAddToQuery()->__call('paginate', func_get_args());
    }
    public function simplePaginate()
    {
        return $this->callAllAddToQuery()->__call('simplePaginate', func_get_args());
    }
    public function clone()
    {
        return $this->callAllAddToQuery()->__call('clone', func_get_args());
    }
    public function callAllAddToQuery()
    {
        if (!$this->funcCalls->isEmpty()) {
            $this->funcCalls->each(function ($fun) {
                if (is_callable($fun)) {
                    $fun();
                }
            });
        }
        return $this;
    }
}
