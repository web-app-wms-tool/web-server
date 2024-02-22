<?php

namespace App\Library\QueryBuilder\Filters;

use App\Library\QueryBuilder\Filters\Custom\FilterScope;
use Illuminate\Support\Collection;

class AllowedFilter
{
    /** @var \App\Library\QueryBuilder\Filters\Filter */
    protected $filterClass;

    /** @var string */
    protected $name;

    /** @var string */
    protected $internalName;

    /** @var \Illuminate\Support\Collection */
    protected $ignored;


    public function __construct(string $name, Filter $filterClass, ?string $internalName = null)
    {
        $this->name = $name;

        $this->filterClass = $filterClass;

        $this->ignored = Collection::make();

        $this->internalName = $internalName ?? $name;
    }

    public static function field(string $name, $internalName = null): self
    {
        return new static($name, new FiltersPartial(), $internalName);
    }
    public static function scope(string $name, string $scope, $internalName = null): self
    {
        return new static($name, new FilterScope($scope), $internalName);
    }
    public static function custom(string $name, Filter $filterClass, ?string $internalName = null): self
    {
        return new static($name, $filterClass, $internalName);
    }

    public function checkFilterCan(string $name)
    {
        return $this->getName() === $name;
    }
    public function getName(): string
    {
        return $this->name;
    }
    public function ignore(...$values): self
    {
        $this->ignored = $this->ignored
            ->merge($values)
            ->flatten();

        return $this;
    }


    protected function resolveValueForFiltering($value)
    {
        if (is_array($value)) {
            $remainingProperties = array_diff_assoc($value, $this->ignored->toArray());

            return !empty($remainingProperties) ? $remainingProperties : null;
        }

        return !$this->ignored->contains($value) ? $value : null;
    }

    public function filter($query, FilterParams $params)
    {
        $valueToFilter = $this->resolveValueForFiltering($params->getValue());

        if (is_null($valueToFilter)) {
            return;
        }
        ($this->filterClass)($query->getEloquentBuilder(), $params, $this->internalName);
    }
}
