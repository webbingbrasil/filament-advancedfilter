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
            static::CLAUSE_EQUAL => 'Is equal to',
            static::CLAUSE_NOT_EQUAL => 'Is not equal to',
            static::CLAUSE_GREATER_OR_EQUAL => 'Is on or after',
            static::CLAUSE_LESS_OR_EQUAL => 'Is on or before',
            static::CLAUSE_GREATER_THAN => 'Is more than',
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

    protected function formatPeriodClause($data): Carbon
    {
        return Carbon::parse(intval($data['value']) . ' ' . ($data['period'] ?? 'days') . ' ' . $data['direction']);
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
                    'weeks' => 'weeks',
                    'months' => 'months',
                    'years' => 'years',
                ])
                ->disableLabel()
                ->placeholder('days')
                ->when(fn ($get) => in_array($get('clause'), [
                    static::CLAUSE_GREATER_THAN,
                    static::CLAUSE_LESS_THAN,
                ])),
            Select::make('direction')
                ->options([
                    'ago' => 'ago'
                ])
                ->disableLabel()
                ->placeholder('from now')
                ->when(fn ($get) => in_array($get('clause'), [
                    static::CLAUSE_GREATER_THAN,
                    static::CLAUSE_LESS_THAN,
                ])),
        ];
    }
}
