<?php

namespace Webbingbrasil\FilamentAdvancedFilter\Concerns;

use Closure;
use Filament\Forms\Components\Component;
use Filament\Forms\Components\Fieldset;
use Filament\Forms\Components\Select;
use Filament\Tables\Filters\Concerns\HasRelationship;
use Illuminate\Database\Eloquent\Builder;

trait HasClauses
{
    use HasRelationship;

    protected string | Closure | null $attribute = null;

    protected string | Closure | null $wrapperFormUsing = null;

    protected bool | Closure $enableClauseLabel = false;

    protected int $debounce = 500;

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

    public function debounce(int $debounce): static
    {
        $this->debounce = $debounce;

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
        if ($this->isHidden()) {
            return $query;
        }

        if ($this->hasQueryModificationCallback()) {
            return parent::apply($query, $data);
        }

        $clause = $data['clause'] ?? null;
        unset($data['clause']);

        if (blank($clause)) {
            return $query;
        }

        if ($this->queriesRelationships()) {
            return $query->whereHas($this->getRelationshipName(), function (Builder $query) use ($clause, $data) {
                $this->applyClause($query, $this->getRelationshipTitleAttribute(), $clause, $data);
            });
        }

        return $this->applyClause($query, $this->getAttribute(), $clause, $data);
    }

    public function getFormSchema(): array
    {
        $fields = $this->fields();
        $clause = Select::make('clause')
            ->label($this->getLabel())
            ->options($this->clauses());

        if ($this->isClauseLabelDisabled()) {
            $clause->hiddenLabel();
        }

        if (filled($defaultState = $this->getDefaultState())) {
            $clause->default($defaultState);
        }

        return $this->evaluate($this->formSchema, [
            'clauseField' => $clause,
            'fields' => $fields,
        ]) ?? [
            $this->getWrapper()
                ->schema(array_merge([$clause], $fields)),
        ];
    }

    public function enableClauseLabel(bool | Closure $condition = true): static
    {
        $this->enableClauseLabel = $condition;

        return $this;
    }

    public function isClauseLabelDisabled(): bool
    {
        return ! $this->evaluate($this->enableClauseLabel);
    }

    public function wrapperUsing(?Closure $callback): static
    {
        $this->wrapperFormUsing = $callback;

        return $this;
    }

    public function getWrapper(): Component
    {
        $wrapperComponent = $this->evaluate($this->wrapperFormUsing);

        if ($wrapperComponent instanceof Component) {
            return $wrapperComponent;
        }

        return Fieldset::make($this->getLabel())->columns(1);
    }

    public function fields(): array
    {
        return [];
    }

    abstract public function clauses(): array;

    abstract protected function applyClause(Builder $query, string $column, string $clause, array $data = []): Builder;
}
