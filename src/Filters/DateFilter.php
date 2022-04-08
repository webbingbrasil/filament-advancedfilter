<?php

namespace Webbingbrasil\FilamentAdvancedFilter\Filters;

use Carbon\Carbon;
use Filament\Tables\Filters\Filter;
use Illuminate\Database\Eloquent\Builder;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Select;
use Webbingbrasil\FilamentAdvancedFilter\Concerns\HasClauses;

class DateFilter extends Filter
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
                    fn (Builder $query, $date): Builder => $query->whereDate($column, '>=', $date),
                )
                ->when(
                    $data['until'],
                    fn (Builder $query, $date): Builder => $query->whereDate($column, '<=', $date),
                );
        }

        $value = match ($clause) {
            static::CLAUSE_LESS_THAN, static::CLAUSE_GREATER_THAN => $this->formatPeriodClause($data),
            default => $data['value']
        };

        $isSetClause = in_array($clause, [static::CLAUSE_NOT_SET, static::CLAUSE_SET]);

        return $query
            ->when(
                $isSetClause,
                fn(Builder $query) => $query->where($column, $operator, null)
            )
            ->when(
                !empty($value) && !$isSetClause,
                fn(Builder $query) => $query->where($column, $operator, $value)
            );
    }

    protected function formatPeriodClause($data): ?Carbon
    {
        if (empty($data['value'])) {
            return null;
        }

        return Carbon::parse(implode(' ', [
            intval($data['value']),
            $data['period'],
            $data['direction']
        ]));
    }

    protected function fields(): array
    {
        return [
            DatePicker::make('value')
                ->when(fn ($get) => !in_array($get('clause'), [
                    static::CLAUSE_GREATER_THAN,
                    static::CLAUSE_LESS_THAN,
                    static::CLAUSE_BETWEEN,
                    static::CLAUSE_NOT_SET,
                    static::CLAUSE_SET,
                    null
                ])),
            DatePicker::make('from')
                ->when(fn ($get) => $get('clause') == static::CLAUSE_BETWEEN),
            DatePicker::make('until')
                ->when(fn ($get) => $get('clause') == static::CLAUSE_BETWEEN),
            TextInput::make('value')
                ->type('number')
                ->minValue(0)
                ->disableLabel()
                ->placeholder('0')
                ->when(fn ($get) => in_array($get('clause'), [
                    static::CLAUSE_GREATER_THAN,
                    static::CLAUSE_LESS_THAN,
                ])),
            Select::make('period')
                ->options([
                    'days' => 'days',
                    'weeks' => 'weeks',
                    'months' => 'months',
                    'years' => 'years',
                ])
                ->disableLabel()
                ->default('days')
                ->disablePlaceholderSelection()
                ->when(fn ($get) => in_array($get('clause'), [
                    static::CLAUSE_GREATER_THAN,
                    static::CLAUSE_LESS_THAN,
                ])),
            Select::make('direction')
                ->options([
                    null => 'from now',
                    'ago' => 'ago'
                ])
                ->disableLabel()
                ->disablePlaceholderSelection()
                ->when(fn ($get) => in_array($get('clause'), [
                    static::CLAUSE_GREATER_THAN,
                    static::CLAUSE_LESS_THAN,
                ])),
        ];
    }
}
