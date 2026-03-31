<?php

namespace Webbingbrasil\FilamentAdvancedFilter\Filters;

use Filament\Forms\Get;
use Filament\Tables\Filters\BaseFilter;
use Filament\Forms\Components\TextInput;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Str;
use Webbingbrasil\FilamentAdvancedFilter\Concerns\HasClauses;

class TextFilter extends BaseFilter
{
    use HasClauses;

    const CLAUSE_EQUAL = 'equal';
    const CLAUSE_NOT_EQUAL = 'not_equal';
    const CLAUSE_START_WITH = 'start_with';
    const CLAUSE_NOT_START_WITH = 'not_start_with';
    const CLAUSE_END_WITH = 'end_with';
    const CLAUSE_NOT_END_WITH = 'not_end_with';
    const CLAUSE_CONTAIN = 'contain';
    const CLAUSE_NOT_CONTAIN = 'not_contain';
    const CLAUSE_SET = 'set';
    const CLAUSE_NOT_SET = 'not_set';

    protected function setUp(): void
    {
        parent::setUp();

        $this->indicateUsing(function (array $state): array {
            if (!isset($state['clause'])) {
                return [];
            }

            if ($state['clause'] === self::CLAUSE_SET || $state['clause'] === self::CLAUSE_NOT_SET) {
                return [$this->getLabel() . ' ' . Str::lower($this->clauses()[$state['clause']])];
            }

            if (!isset($state['value'])) {
                return [];
            }

            if ($state['clause'] && $state['value']) {
                return [$this->getLabel() . ' ' . Str::lower($this->clauses()[$state['clause']]) . ' "' . $state['value'] . '"'];
            }

            return [];
        });
    }

    public function clauses(): array
    {
        return [
            static::CLAUSE_EQUAL => __('filament-advancedfilter::clauses.equal'),
            static::CLAUSE_NOT_EQUAL => __('filament-advancedfilter::clauses.not_equal'),
            static::CLAUSE_START_WITH => __('filament-advancedfilter::clauses.start_with'),
            static::CLAUSE_NOT_START_WITH => __('filament-advancedfilter::clauses.not_start_with'),
            static::CLAUSE_END_WITH => __('filament-advancedfilter::clauses.end_with'),
            static::CLAUSE_NOT_END_WITH => __('filament-advancedfilter::clauses.not_end_with'),
            static::CLAUSE_CONTAIN => __('filament-advancedfilter::clauses.contain'),
            static::CLAUSE_NOT_CONTAIN => __('filament-advancedfilter::clauses.not_contain'),
            static::CLAUSE_SET => __('filament-advancedfilter::clauses.set'),
            static::CLAUSE_NOT_SET => __('filament-advancedfilter::clauses.not_set'),
        ];
    }

    protected function applyClause(Builder $query, string $column, string $clause, array $data = []): Builder
    {
        $operator = match ($clause) {
            static::CLAUSE_NOT_START_WITH, static::CLAUSE_NOT_END_WITH, self::CLAUSE_NOT_CONTAIN => 'not like',
            static::CLAUSE_START_WITH, static::CLAUSE_END_WITH, self::CLAUSE_CONTAIN => 'like',
            static::CLAUSE_NOT_EQUAL, static::CLAUSE_SET => '!=',
            default => '='
        };

        if (!in_array($clause, [static::CLAUSE_SET, static::CLAUSE_NOT_SET]) && blank($data['value'])) {
            return $query;
        }

        $value = match ($clause) {
            self::CLAUSE_NOT_SET, self::CLAUSE_SET => null,
            self::CLAUSE_NOT_START_WITH, self::CLAUSE_START_WITH => $data['value'] . '%',
            self::CLAUSE_NOT_END_WITH, self::CLAUSE_END_WITH => '%' . $data['value'],
            self::CLAUSE_NOT_CONTAIN, self::CLAUSE_CONTAIN => '%' . $data['value'] . '%',
            default => $data['value'],
        };

        return $query->where($column, $operator, $value);
    }

    public function fields(): array
    {
        return [
            TextInput::make('value')
                ->debounce($this->debounce)
                ->hidden(fn (Get $get) => in_array(
                    $get('clause'),
                    [self::CLAUSE_NOT_SET, self::CLAUSE_SET]
                ) || empty($get('clause')))
                ->hiddenLabel(),
        ];
    }
}
