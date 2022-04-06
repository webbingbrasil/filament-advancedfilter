<?php

namespace Webbingbrasil\FilamentAdvancedFilter;

use Filament\Forms\Components\Fieldset;
use Filament\Forms\Components\Select;
use Filament\Tables\Filters\Concerns\HasRelationship;
use Filament\Tables\Filters\Filter;
use Closure;
use Illuminate\Database\Eloquent\Builder;

abstract class AdvancedFilter extends Filter
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

        if (blank($data['clause'] ?? null)) {
            return $query;
        }

        if ($this->queriesRelationships()) {
            return $query->whereHas($this->getRelationshipName(), function ($query) use ($data) {
                $this->applyFilter($query, $this->getRelationshipKey(), $data);
            });
        }

        return $this->applyFilter($query, $this->getColumn(), $data);
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

    abstract protected function applyFilter(Builder $query, string $column, array $data = []): Builder;

}
