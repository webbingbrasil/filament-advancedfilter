<?php

namespace Webbingbrasil\FilamentAdvancedFilter\Filters;

use Filament\Tables\Filters\Filter;
use Illuminate\Database\Eloquent\Builder;
use Filament\Forms\Components\TextInput;
use Webbingbrasil\FilamentAdvancedFilter\Concerns\HasClauses;

class NumberFilter extends Filter
{
    use HasClauses;

    const CLAUSE_EQUAL = 'equal';
    const CLAUSE_NOT_EQUAL = 'not_equal';
    const CLAUSE_GREATER_OR_EQUAL = 'greater_equal';
    const CLAUSE_LESS_OR_EQUAL = 'less_equal';
    const CLAUSE_GREATER_THAN = 'greater_than';
    const CLAUSE_LESS_THAN = 'less_than';
    const CLAUSE_BETWEEN = 'between';
    const CLAUSE_SET = 'set';
    const CLAUSE_NOT_SET = 'not_set';

    protected function clauses(): array
    {
        return [
            static::CLAUSE_EQUAL => 'Is equal to',
            static::CLAUSE_NOT_EQUAL => 'Is not equal to',
            static::CLAUSE_GREATER_OR_EQUAL => 'Is greater than or equal to',
            static::CLAUSE_LESS_OR_EQUAL => 'Is less than or equal to',
            static::CLAUSE_GREATER_THAN => 'Is greater than',
            static::CLAUSE_LESS_THAN => 'Is less than',
            static::CLAUSE_BETWEEN => 'Is between',
            static::CLAUSE_SET => 'Is set',
            static::CLAUSE_NOT_SET => 'Is not set',
        ];
    }

    protected function applyClause(Builder $query, string $column, string $clause, array $data = []): Builder
    {
        $operator = match ($clause) {
            static::CLAUSE_EQUAL, static::CLAUSE_NOT_SET => '=',
            static::CLAUSE_NOT_EQUAL, static::CLAUSE_SET => '!=',
            static::CLAUSE_GREATER_OR_EQUAL => '>=',
            static::CLAUSE_LESS_OR_EQUAL => '<=',
            static::CLAUSE_GREATER_THAN => '>',
            static::CLAUSE_LESS_THAN => '<',
            default => $clause
        };

        if ($operator === static::CLAUSE_BETWEEN) {
            return $query
                ->when(
                    $data['from'],
                    fn (Builder $query, $value): Builder => $query->where($column, '>=', $value),
                )
                ->when(
                    $data['until'],
                    fn (Builder $query, $value): Builder => $query->where($column, '<=', $value),
                );
        }

        $isSetClause = in_array($clause, [static::CLAUSE_NOT_SET, static::CLAUSE_SET]);

        return $query
            ->when(
                $isSetClause,
                fn(Builder $query) => $query->where($column, $operator, null)
            )
            ->when(
                !empty($data['value']) && !$isSetClause,
                fn(Builder $query) => $query->where($column, $operator, $data['value'])
            );
    }

    protected function fields(): array
    {
        return [
            TextInput::make('value')
                ->type('number')
                ->disableLabel()
                ->placeholder('0')
                ->when(fn ($get) => !in_array($get('clause'), [
                    static::CLAUSE_BETWEEN,
                    static::CLAUSE_NOT_SET,
                    static::CLAUSE_SET,
                    null
                ])),
            TextInput::make('from')
                ->type('number')
                ->when(fn ($get) => $get('clause') == static::CLAUSE_BETWEEN),
            TextInput::make('until')
                ->type('number')
                ->when(fn ($get) => $get('clause') == static::CLAUSE_BETWEEN),
        ];
    }
}
