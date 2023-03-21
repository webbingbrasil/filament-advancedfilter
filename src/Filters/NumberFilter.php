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

    protected function setUp(): void
    {
        parent::setUp();

        $this->indicateUsing(function (array $state): array {
            if (isset($state['clause'])) {
                if ($state['clause'] === self::CLAUSE_SET || $state['clause'] === self::CLAUSE_NOT_SET) {
                    return [$this->getLabel() . ' ' . $this->clauses()[$state['clause']]];
                }
                if ($state['value']) {
                    return [$this->getLabel() . ' ' . $this->clauses()[$state['clause']] . ' ' . $state['value']];
                }
                if ($state['from'] || $state['until']) {
                    return [$this->getLabel() . ' ' . $this->clauses()[$state['clause']] . ' ' . ($state['from'] ?? 0) . ' and ' . ($state['until'] ?? "~")];
                }
            }

            return [];
        });
    }

    protected function clauses(): array
    {
        return [
            static::CLAUSE_EQUAL => __('filament-advancedfilter::clauses.equal'),
            static::CLAUSE_NOT_EQUAL => __('filament-advancedfilter::clauses.not_equal'),
            static::CLAUSE_GREATER_OR_EQUAL => __('filament-advancedfilter::clauses.greater_equal'),
            static::CLAUSE_LESS_OR_EQUAL => __('filament-advancedfilter::clauses.less_equal'),
            static::CLAUSE_GREATER_THAN => __('filament-advancedfilter::clauses.greater_than'),
            static::CLAUSE_LESS_THAN => __('filament-advancedfilter::clauses.less_than'),
            static::CLAUSE_BETWEEN => __('filament-advancedfilter::clauses.between'),
            static::CLAUSE_SET => __('filament-advancedfilter::clauses.set'),
            static::CLAUSE_NOT_SET => __('filament-advancedfilter::clauses.not_set'),
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
                fn (Builder $query) => $query->where($column, $operator, null)
            )
            ->when(
                !empty($data['value']) && !$isSetClause,
                fn (Builder $query) => $query->where($column, $operator, $data['value'])
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
                ->label(__('filament-advancedfilter::clauses.from'))
                ->type('number')
                ->when(fn ($get) => $get('clause') == static::CLAUSE_BETWEEN),
            TextInput::make('until')
                - label(__('filament-advancedfilter::clauses.until'))
                ->type('number')
                ->when(fn ($get) => $get('clause') == static::CLAUSE_BETWEEN),
        ];
    }
}
