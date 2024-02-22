<?php

namespace App\Helpers;

use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class AggridHelper
{

    public static function convertFilterType($filter, $query, $key, $where = 'where')
    {
        switch ($filter['filterType']) {
            case 'date-custom':
                $query = AggridHelper::convertDateCusotomFilterType($filter, $query, $key, $where);
                break;
            case 'date':
                $query = AggridHelper::convertDateFilterType($filter, $query, $key, $where);
                break;
            default:
                $query = AggridHelper::convertTextFilterType($filter, $query, $key, $where);
                break;
        }
        return $query;
    }
    public static function convertDateCusotomFilterType($filter, $query, $key, $where = 'where')
    {
        if (isset($filter['filter']))
            $filter['filter'] =
                Carbon::createFromFormat(config('app.format_datetime_send'), $filter['filter']);

        if (isset($filter['filterTo']))
            $filter['filterTo'] =
                Carbon::createFromFormat(config('app.format_datetime_send'), $filter['filterTo']);
        return AggridHelper::convertTextFilterType($filter, $query, $key, $where);
    }
    public static function convertDateFilterType($filter, $query, $key, $where = 'where')
    {
        $filter['filter'] = $filter['dateFrom'] ?? $filter['filter'] ?? '';
        $filter['filter'] = explode(" ", $filter['filter'])[0];
        $filter['filterTo'] = $filter['dateTo'] ?? $filter['filterTo'] ?? '';
        $filter['filterTo'] = explode(" ", $filter['filterTo'])[0];
        return AggridHelper::convertTextFilterType($filter, $query, DB::raw("DATE($key)"), $where);
    }
    public static function convertDateTimeFilterType($filter, $query, $key, $where = 'where')
    {
        $filter['filter'] = $filter['dateFrom'] ?? $filter['filter'] ?? '';
        $filter['filterTo'] = $filter['dateTo'] ?? $filter['filterTo'] ?? '';
        return AggridHelper::convertTextFilterType($filter, $query, $key, $where);
    }
    public static function convertTextFilterType($filter, $query, $key, $where = 'whereRaw')
    {
        $value = $filter['filter'] ?? null;
        $value = str_replace(["%", "_", "^", "@"], ["\%", "\_", "\^", "\@"], $value);
        if (isset($filter['type'])) {
            switch ($filter['type']) {
                case 'contains':
                    $value = mb_strtolower($value);
                    $query->{$where}($key, 'ilike', '%' . $value . '%');
                    break;
                case 'notContains':
                    $value = mb_strtolower($value);
                    $query->{$where}($key, 'not ilike', '%' . $value . '%');
                    break;
                case 'equal':
                case 'equals':
                    $query->{$where}($key, '=',  $value);
                    break;
                case 'notEqual':
                    $query->{$where}($key, '!=',  $value);
                    break;
                case 'startsWith':
                    $value = mb_strtolower($value);
                    $query->{$where}($key, 'ilike',  $value . '%');
                    break;
                case 'endsWith':
                    $value = mb_strtolower($value);
                    $query->{$where}($key, 'ilike',  '%' . $value);
                    break;
                case 'null':
                    if ($filter['filterType'] == 'text') {
                        $query->{$where}(function ($query) use ($key) {
                            $query->whereNull($key);
                            $query->orWhere($key, "");
                        });
                    } else {
                        $query->{$where . 'Null'}($key);
                    }
                    break;
                case 'notNull':
                    if ($filter['filterType'] == 'text') {
                        $query->{$where}(function ($query) use ($key) {
                            $query->whereNotNull($key);
                            $query->where($key, '!=', "");
                        });
                    } else {
                        $query->{$where . 'NotNull'}($key);
                    }
                    break;
                case 'blank':
                    if ($filter['filterType'] == 'text') {
                        $query->{$where}(function ($query) use ($key) {
                            $query->whereNull($key);
                            $query->orWhere($key, "");
                        });
                    } else {
                        $query->{$where . 'Null'}($key);
                    }
                    break;
                case 'notBlank':
                    if ($filter['filterType'] == 'text') {
                        $query->{$where}(function ($query) use ($key) {
                            $query->whereNotNull($key);
                            $query->where($key, '!=', "");
                        });
                    } else {
                        $query->{$where . 'NotNull'}($key);
                    }
                    break;
                case 'lessThan':
                    $query->{$where}($key, '<',  $value);
                    break;
                case 'lessThanOrEqual':
                    $query->{$where}($key, '<=',  $value);
                    break;
                case 'greaterThan':
                    $query->{$where}($key, '>',  $value);
                    break;
                case 'greaterThanOrEqual':
                    $query->{$where}($key, '>=',  $value);
                    break;
                case 'inRange':
                    $query->{$where . 'Between'}($key,  [$value, $filter['filterTo']]);
                    break;
            }
        }
        if (isset($filter['filterType']) && $filter['filterType'] == 'set') {
            $query->whereIn($key, $filter["values"]);
        }
        return $query;
    }
}
