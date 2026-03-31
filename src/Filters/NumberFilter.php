<?php

namespace Webbingbrasil\FilamentAdvancedFilter\Filters;

use Filament\Forms\Get;
use Filament\Tables\Filters\BaseFilter;
use Filament\Forms\Components\TextInput;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Str;
use Webbingbrasil\FilamentAdvancedFilter\Concerns\HasClauses;

class NumberFilter extends BaseFilter
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
            if (isset($state['clause']) && !empty($state['clause'])) {
                if ($state['clause'] === self::CLAUSE_SET || $state['clause'] === self::CLAUSE_NOT_SET) {
                    return [$this->getLabel() . ' ' . Str::lower($this->clauses()[$state['clause']])];
                }
                if ($state['clause'] === self::CLAUSE_BETWEEN) {
                    return [$this->getLabel() . ' ' . Str::lower($this->clauses()[$state['clause']]) . ' ' . ($state['from'] ?? 0) .
                    ' ' . __('filament-advancedfilter::clauses.between_and') . ' ' .
                    ($state['until'] ?? "~")];
                }
                if ($state['value']) {
                    return [$this->getLabel() . ' ' . Str::lower($this->clauses()[$state['clause']]) . ' ' . $state['value']];
                }
            }

            return [];
        });
    }

    public function clauses(): array
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
                is_numeric($data['value']) && !$isSetClause,
                fn (Builder $query) => $query->where($column, $operator, $data['value'])
            );
    }

    public function fields(): array
    {
        return [
            TextInput::make('value')
                ->type('number')
                ->debounce($this->debounce)
                ->hiddenLabel()
                ->placeholder('0')
                ->visible(fn (Get $get) => !in_array($get('clause'), [
                    static::CLAUSE_BETWEEN,
                    static::CLAUSE_NOT_SET,
                    static::CLAUSE_SET,
                    null
                ])),
            TextInput::make('from')
                ->label(__('filament-advancedfilter::clauses.from'))
                ->type('number')
                ->debounce($this->debounce)
                ->visible(fn (Get $get) => $get('clause') == static::CLAUSE_BETWEEN),
            TextInput::make('until')
                ->label(__('filament-advancedfilter::clauses.until'))
                ->type('number')
                ->debounce($this->debounce)
                ->visible(fn (Get $get) => $get('clause') == static::CLAUSE_BETWEEN),
        ];
    }
}
