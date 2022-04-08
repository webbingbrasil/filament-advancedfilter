<?php

namespace Webbingbrasil\FilamentAdvancedFilter\Filters;

use Filament\Tables\Filters\Filter;
use Illuminate\Database\Eloquent\Builder;
use Filament\Forms\Components\TextInput;
use Webbingbrasil\FilamentAdvancedFilter\Concerns\HasClauses;

class TextFilter extends Filter
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

    protected function clauses(): array
    {
        return [
            static::CLAUSE_EQUAL => 'Is equal to',
            static::CLAUSE_NOT_EQUAL => 'Is not equal to',
            static::CLAUSE_START_WITH => 'Start with',
            static::CLAUSE_NOT_START_WITH => 'Not start with',
            static::CLAUSE_END_WITH => 'End with',
            static::CLAUSE_NOT_END_WITH => 'Not end with',
            static::CLAUSE_CONTAIN => 'Contain',
            static::CLAUSE_NOT_CONTAIN => 'Not contain',
            static::CLAUSE_SET => 'Is set',
            static::CLAUSE_NOT_SET => 'Is not set',
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

        $value = match ($clause) {
            self::CLAUSE_NOT_SET, self::CLAUSE_SET => null,
            self::CLAUSE_NOT_START_WITH, self::CLAUSE_START_WITH => $data['value'] . '%',
            self::CLAUSE_NOT_END_WITH, self::CLAUSE_END_WITH => '%' . $data['value'],
            self::CLAUSE_NOT_CONTAIN, self::CLAUSE_CONTAIN => '%' . $data['value'] . '%',
            default => $data['value'],
        };

        return $query->where($column, $operator, $value);
    }

    protected function fields(): array
    {
        return [
            TextInput::make('value')
                ->hidden(fn($get) => in_array(
                        $get('clause'),
                        [self::CLAUSE_NOT_SET, self::CLAUSE_SET]
                    ) || !empty($get('clause')))
                ->disableLabel(),
        ];
    }
}
