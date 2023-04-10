<?php

namespace Webbingbrasil\FilamentAdvancedFilter\Filters;

use Carbon\Carbon;
use Filament\Tables\Filters\BaseFilter;
use Illuminate\Database\Eloquent\Builder;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Select;
use Illuminate\Support\Str;
use Webbingbrasil\FilamentAdvancedFilter\Concerns\HasClauses;

class DateFilter extends BaseFilter
{
    use HasClauses;

    const CLAUSE_YESTERDAY = 'yesterday';
    const CLAUSE_TODAY = 'today';
    const CLAUSE_TOMORROW = 'tomorrow';
    const CLAUSE_IN_THIS = 'in_this';
    const CLAUSE_EQUAL = 'equal';
    const CLAUSE_NOT_EQUAL = 'not_equal';
    const CLAUSE_ON_AFTER = 'on_after';
    const CLAUSE_ON_BEFORE = 'on_before';
    const CLAUSE_GREATER_THAN = 'greater_than';
    const CLAUSE_LESS_THAN = 'less_than';
    const CLAUSE_IN_THE_LAST = 'in_the_last';
    const CLAUSE_IN_THE_NEXT = 'in_the_next';
    const CLAUSE_IN_THE_RANGE = 'in_the_range';
    const CLAUSE_SET = 'set';
    const CLAUSE_NOT_SET = 'not_set';

    protected function setUp(): void
    {
        parent::setUp();

        $this->indicateUsing(function (array $state): array {
            if (isset($state['clause']) && !empty($state['clause'])) {
                $message = $this->getLabel() . ' ' . $this->clauses()[$state['clause']];

                if (in_array($state['clause'], [
                    self::CLAUSE_SET, self::CLAUSE_NOT_SET,
                    self::CLAUSE_YESTERDAY, self::CLAUSE_TODAY,
                    self::CLAUSE_TOMORROW
                ])) {
                    return [$message];
                }

                if ($state['clause'] === self::CLAUSE_IN_THIS) {
                    $period = __('filament-advancedfilter::clauses.'.$state['this_period']);
                    return [$message . ' ' . $period];
                }

                if (
                    $state['period_value']
                    && in_array($state['clause'], [self::CLAUSE_IN_THE_LAST, self::CLAUSE_IN_THE_NEXT])
                ) {
                    $period = __('filament-advancedfilter::clauses.'.$state['period']);
                    return [$message . ' ' . $state['period_value'] . ' ' . $period];
                }

                if (
                    $state['period_value']
                    && in_array($state['clause'], [self::CLAUSE_GREATER_THAN, self::CLAUSE_LESS_THAN])
                ) {
                    $direction = __('filament-advancedfilter::clauses.' . ($state['direction'] ?: 'from_now'));
                    $period = __('filament-advancedfilter::clauses.'.$state['period']);
                    return [$message . ' ' . $state['period_value'] . ' ' . $period . ' ' . $direction];
                }

                if ($state['value']) {
                    return [$message . ' ' . Carbon::parse($state['value'])->format(config('tables.date_format', 'Y-m-d'))];
                }

                if ($state['from'] || $state['until']) {
                    return [
                        $message . ' ' .
                            ($state['from'] ? Carbon::parse($state['from'])->format(config('tables.date_format', 'Y-m-d')) : 0) . ' and ' .
                            ($state['until'] ? Carbon::parse($state['until'])->format(config('tables.date_format', 'Y-m-d')) : "~")
                    ];
                }
            }

            return [];
        });
    }

    public function clauses(): array
    {
        return [
            static::CLAUSE_YESTERDAY => __('filament-advancedfilter::clauses.yesterday'),
            static::CLAUSE_TODAY => __('filament-advancedfilter::clauses.today'),
            static::CLAUSE_TOMORROW => __('filament-advancedfilter::clauses.tomorrow'),
            static::CLAUSE_IN_THIS => __('filament-advancedfilter::clauses.in_this'),
            static::CLAUSE_IN_THE_LAST => __('filament-advancedfilter::clauses.in_the_last'),
            static::CLAUSE_IN_THE_NEXT => __('filament-advancedfilter::clauses.in_the_next'),
            static::CLAUSE_GREATER_THAN => __('filament-advancedfilter::clauses.date_after'),
            static::CLAUSE_LESS_THAN => __('filament-advancedfilter::clauses.date_before'),
            static::CLAUSE_EQUAL => __('filament-advancedfilter::clauses.equal'),
            static::CLAUSE_NOT_EQUAL => __('filament-advancedfilter::clauses.not_equal'),
            static::CLAUSE_ON_AFTER => __('filament-advancedfilter::clauses.on_after'),
            static::CLAUSE_ON_BEFORE => __('filament-advancedfilter::clauses.on_before'),
            static::CLAUSE_IN_THE_RANGE => __('filament-advancedfilter::clauses.in_the_range'),
            static::CLAUSE_SET => __('filament-advancedfilter::clauses.set'),
            static::CLAUSE_NOT_SET => __('filament-advancedfilter::clauses.not_set'),
        ];
    }

    protected function applyClause(Builder $query, string $column, string $clause, array $data = []): Builder
    {
        $operator = match ($clause) {
            static::CLAUSE_EQUAL, static::CLAUSE_NOT_SET,
            static::CLAUSE_TODAY, static::CLAUSE_YESTERDAY, static::CLAUSE_TOMORROW => '=',
            static::CLAUSE_NOT_EQUAL, static::CLAUSE_SET => '!=',
            static::CLAUSE_ON_AFTER => '>=',
            static::CLAUSE_ON_BEFORE => '<=',
            static::CLAUSE_GREATER_THAN => '>',
            static::CLAUSE_LESS_THAN => '<',
            default => $clause
        };

        if (in_array($operator, [static::CLAUSE_IN_THE_RANGE, static::CLAUSE_IN_THE_LAST, static::CLAUSE_IN_THE_NEXT, static::CLAUSE_IN_THIS])) {
            $fromDate = $data['from'] ?? null;
            $untilDate = $data['until'] ?? null;

            if ($operator === static::CLAUSE_IN_THIS) {
                $fromDate = Carbon::today()->startOf($data['this_period']);
                $untilDate = Carbon::today()->endOf($data['this_period']);
            }

            if ($operator === static::CLAUSE_IN_THE_LAST) {
                $data['direction'] = 'ago';
                $fromDate = $this->formatPeriodClause($data);
                $untilDate = Carbon::today();
            }

            if ($operator === static::CLAUSE_IN_THE_NEXT) {
                $data['direction'] = null;
                $fromDate = Carbon::today();
                $untilDate = $this->formatPeriodClause($data);
            }


            return $query
                ->when(
                    $fromDate,
                    fn (Builder $query, $date): Builder => $query->whereDate($column, '>=', $date),
                )
                ->when(
                    $untilDate,
                    fn (Builder $query, $date): Builder => $query->whereDate($column, '<=', $date),
                );
        }

        $value = match ($clause) {
            static::CLAUSE_LESS_THAN, static::CLAUSE_GREATER_THAN => $this->formatPeriodClause($data),
            static::CLAUSE_TODAY, static::CLAUSE_YESTERDAY, static::CLAUSE_TOMORROW => Carbon::parse($clause),
            default => $data['value']
        };

        $isSetClause = in_array($clause, [static::CLAUSE_NOT_SET, static::CLAUSE_SET]);

        return $query
            ->when(
                $isSetClause,
                fn (Builder $query) => $query->where($column, $operator, null)
            )
            ->when(
                !empty($value) && !$isSetClause,
                fn (Builder $query) => $query->whereDate($column, $operator, $value)
            );
    }

    protected function formatPeriodClause($data): ?Carbon
    {
        $value = (int)$data['period_value'];
        $period = $data['period'];
        if (empty($value)) {
            return null;
        }

        if ($period === 'quarters') {
            $value *= 3;
            $period = 'months';
        }

        return Carbon::parse(implode(' ', [
            $value,
            $period,
            $data['direction']
        ]));
    }

    public function fields(): array
    {
        return [
            DatePicker::make('value')
                ->disableLabel()
                ->when(fn ($get) => !in_array($get('clause'), [
                    static::CLAUSE_YESTERDAY,
                    static::CLAUSE_TODAY,
                    static::CLAUSE_TOMORROW,
                    static::CLAUSE_GREATER_THAN,
                    static::CLAUSE_LESS_THAN,
                    static::CLAUSE_IN_THIS,
                    static::CLAUSE_IN_THE_LAST,
                    static::CLAUSE_IN_THE_NEXT,
                    static::CLAUSE_IN_THE_RANGE,
                    static::CLAUSE_NOT_SET,
                    static::CLAUSE_SET,
                    null
                ])),
            DatePicker::make('from')
                ->label(__('filament-advancedfilter::clauses.from'))
                ->when(fn ($get) => $get('clause') == static::CLAUSE_IN_THE_RANGE),
            DatePicker::make('until')
                ->label(__('filament-advancedfilter::clauses.until'))
                ->when(fn ($get) => $get('clause') == static::CLAUSE_IN_THE_RANGE),
            TextInput::make('period_value')
                ->type('number')
                ->debounce($this->debounce)
                ->minValue(0)
                ->disableLabel()
                ->placeholder('0')
                ->when(fn ($get) => in_array($get('clause'), [
                    static::CLAUSE_GREATER_THAN,
                    static::CLAUSE_LESS_THAN,
                    static::CLAUSE_IN_THE_LAST,
                    static::CLAUSE_IN_THE_NEXT,
                ])),
            Select::make('period')
                ->options([
                    'days' => __('filament-advancedfilter::clauses.days'),
                    'weeks' => __('filament-advancedfilter::clauses.weeks'),
                    'months' => __('filament-advancedfilter::clauses.months'),
                    'quarters' => __('filament-advancedfilter::clauses.quarters'),
                    'years' => __('filament-advancedfilter::clauses.years'),
                ])
                ->disableLabel()
                ->default('days')
                ->disablePlaceholderSelection()
                ->when(fn ($get) => in_array($get('clause'), [
                    static::CLAUSE_GREATER_THAN,
                    static::CLAUSE_LESS_THAN,
                    static::CLAUSE_IN_THE_LAST,
                    static::CLAUSE_IN_THE_NEXT,
                ])),
            Select::make('this_period')
                ->options([
                    'day' => __('filament-advancedfilter::clauses.day'),
                    'week' => __('filament-advancedfilter::clauses.week'),
                    'month' => __('filament-advancedfilter::clauses.month'),
                    'quarter' => __('filament-advancedfilter::clauses.quarter'),
                    'year' => __('filament-advancedfilter::clauses.year'),
                ])
                ->disableLabel()
                ->default('days')
                ->disablePlaceholderSelection()
                ->when(fn ($get) => $get('clause') === static::CLAUSE_IN_THIS),
            Select::make('direction')
                ->options([
                    null => __('filament-advancedfilter::clauses.from_now'),
                    'ago' => __('filament-advancedfilter::clauses.ago')
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
