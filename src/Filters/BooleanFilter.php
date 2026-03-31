<?php

namespace Webbingbrasil\FilamentAdvancedFilter\Filters;

use Filament\Tables\Filters\BaseFilter;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Str;
use Webbingbrasil\FilamentAdvancedFilter\Concerns\HasClauses;

class BooleanFilter extends BaseFilter
{
    use HasClauses;

    const CLAUSE_IS_TRUE = 'true';
    const CLAUSE_IS_FALSE = 'false';
    const CLAUSE_SET = 'set';
    const CLAUSE_NOT_SET = 'not_set';

    protected bool $showUnknowns = false;
    protected ?bool $nullsAre = null;

    protected function setUp(): void
    {
        parent::setUp();

        $this->indicateUsing(function (array $state): array {
            return isset($state['clause']) && !empty($state['clause'])
                ? [$this->getLabel() . ' ' . Str::lower($this->clauses()[$state['clause']])]
                : [];
        });
    }

    public function clauses(): array
    {
        return [
            static::CLAUSE_IS_TRUE => __('filament-advancedfilter::clauses.true'),
            static::CLAUSE_IS_FALSE => __('filament-advancedfilter::clauses.false'),
        ] + ($this->showUnknowns && $this->nullsAre === null ? [
            self::CLAUSE_SET => __('filament-advancedfilter::clauses.set'),
            self::CLAUSE_NOT_SET => __('filament-advancedfilter::clauses.not_set'),
        ] : []);
    }

    protected function applyClause(Builder $query, string $column, string $clause, array $data = []): Builder
    {
        $operator = match ($clause) {
            static::CLAUSE_SET => '!=',
            default => '='
        };

        $value = match ($clause) {
            self::CLAUSE_IS_TRUE => true,
            self::CLAUSE_IS_FALSE => false,
            default => null,
        };

        if ($this->nullsAre !== null && $this->nullsAre === $value) {
            return $query->where(
                fn (Builder $query) =>
                $query->where($column, $operator, $value)->orWhereNull($column)
            );
        }

        return $query->where($column, $operator, $value);
    }

    public function showUnknowns(): static
    {
        $this->showUnknowns = true;

        return $this->nullsAreUnknown();
    }

    public function hideUnknowns(): static
    {
        $this->showUnknowns = false;

        return $this;
    }

    public function nullsAreTrue(): static
    {
        $this->nullsAre = true;

        return $this;
    }

    public function nullsAreFalse(): static
    {
        $this->nullsAre = false;

        return $this;
    }

    public function nullsAreUnknown(): static
    {
        $this->nullsAre = null;

        return $this;
    }
}
