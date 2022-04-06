<?php

namespace Webbingbrasil\FilamentAdvancedFilter\Filters;

use Illuminate\Database\Eloquent\Builder;
use Webbingbrasil\FilamentAdvancedFilter\AdvancedFilter;

class BooleanFilter extends AdvancedFilter
{

    const CLAUSE_IS_TRUE = 'true';
    const CLAUSE_IS_FALSE = 'false';

    protected function clauses(): array
    {
        return [
            static::CLAUSE_IS_TRUE => 'Is true',
            static::CLAUSE_IS_FALSE => 'Is false',
        ];
    }

    protected function applyFilter(Builder $query, string $column, array $data = []): Builder
    {
        $operator = '=';
        $value = $data['clause'] === self::CLAUSE_IS_TRUE;

        return $query->where($column, $operator, $value);
    }
}
