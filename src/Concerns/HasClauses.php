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

    protected string | Closure | null $attribute = null;

    /** @deprecated use `->attribute()` on the filter instead */
    public function column(string | Closure | null $name): static
    {
        return $this->attribute($name);
    }

    public function attribute(string | Closure | null $name): static
    {
        $this->attribute = $name;

        return $this;
    }

    /** @deprecated use `->getAttribute()` instead */
    public function getColumn(): string
    {
        return $this->getAttribute();
    }


    public function getAttribute(): string
    {
        return $this->evaluate($this->attribute) ?? $this->getName();
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

        return $this->applyClause($query, $this->getAttribute(), $clause, $data);
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

    public function fields(): array
    {
        return [];
    }

    abstract public function clauses(): array;

    abstract protected function applyClause(Builder $query, string $column, string $clause, array $data = []): Builder;

}
