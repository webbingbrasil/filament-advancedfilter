<?php

namespace Webbingbrasil\FilamentAdvancedFilter\Concerns;

use Filament\Forms\Components\Fieldset;
use Filament\Forms\Components\Select;
use Filament\Tables\Filters\Concerns\HasRelationship;
use Closure;
use Illuminate\Database\Eloquent\Builder;

trait HasClauses
{
    use HasRelationship;

    protected string | Closure | null $column = null;

    public function column(string | Closure | null $name): static
    {
        $this->column = $name;

        return $this;
    }

    public function getColumn(): string
    {
        return $this->evaluate($this->column) ?? $this->getName();
    }

    public function apply(Builder $query, array $data = []): Builder
    {
        if ($this->hasQueryModificationCallback()) {
            return parent::apply($query, $data);
        }

        $clause = $data['clause'] ?? null;
        unset($data['clause']);

        if (blank($clause)) {
            return $query;
        }

        if ($this->queriesRelationships()) {
            return $query->whereHas($this->getRelationshipName(), function ($query) use ($clause, $data) {
                $this->applyClause($query, $this->getRelationshipTitleColumnName(), $clause, $data);
            });
        }

        return $this->applyClause($query, $this->getColumn(), $clause, $data);
    }

    public function getFormSchema(): array
    {
        return $this->evaluate($this->formSchema) ?? [
                Fieldset::make($this->getLabel())
                    ->columns(1)
                    ->schema(array_merge([
                        Select::make('clause')
                            ->disableLabel()
                            ->options($this->clauses()),
                    ], $this->fields()))
            ];
    }

    protected function fields(): array
    {
        return [];
    }

    abstract protected function clauses(): array;

    abstract protected function applyClause(Builder $query, string $column, string $clause, array $data = []): Builder;

}
