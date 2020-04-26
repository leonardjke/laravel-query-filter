<?php

namespace Leonardjke\LaravelQueryFilter;

/**
 *	This file is part of the Leonardjke\LaravelQueryFilter package for Laravel.
 *
 *	@license http://opensource.org/licenses/MIT MIT
 */

use Illuminate\Support\Str;
use Illuminate\Http\Request;
use Illuminate\Database\Eloquent\Builder;

trait FilterableTrait {

    public $filterable;

    /**
     * @param Builder $filter
     * @param Request $request
     * @return Builder
     */
    public function scopeFilter(Builder $filter, Request $request)
    {
        $this->filterColumns = $this->filterColumns ?? [];

        return $filter->where(function (Builder $query) use ($request) {
            $bools = $request->get('bool');
            $operators = $request->get('operator');

            foreach ($this->filterColumns as $alias => $column) {
                if ($request->has($alias) || ($request->has('sort')) && Str::contains($request->get('sort'), $alias)) {
                    $value = $request->get($alias);

                    $operatorInWhere = '=';
                    if (!is_null($operators) &&
                        is_array($operators) &&
                        array_key_exists($alias, $operators) &&
                        in_array($operators[$alias], ['=', '<', '<=', '>', '>=', '!=', 'like'])
                    ) {
                        $operatorInWhere = $operators[$alias];
                    }

                    if (Str::contains($column, '.')) {
                        [$relation, $column] = explode('.', $column);

                        if (!method_exists($this, $relation)) {
                            continue;
                        }

                        $methodOfWhere = 'whereHas';

                        if (!is_null($bools) &&
                            is_array($bools) &&
                            array_key_exists($alias, $bools) &&
                            $bools[$alias] === 'or'
                        ) {
                            $methodOfWhere = 'orWhereHas';
                        }

                        if (is_array($value)) {
                            $methodOfWhere .= 'In';
                        }

                        $query->$methodOfWhere($relation, function (Builder $relationQuery) use ($column, $operatorInWhere, $value) {
                            return $relationQuery->where($column, $operatorInWhere, $value);
                        });
                    } else {
                        $methodOfWhere = 'where';

                        if (!is_null($bools) &&
                            is_array($bools) &&
                            array_key_exists($value, $bools) &&
                            $bools[$alias] === 'or'
                        ) {
                            $methodOfWhere = 'orWhere';
                        }

                        if (is_array($value)) {
                            $methodOfWhere .= 'In';
                        }
                        $query->$methodOfWhere($column, $operatorInWhere, $value);
                    }
                }
            }

            return $query;
        });
    }

}
