<?php

namespace Kirameki\Database\Schema\Statements;

class AlterColumnAction
{
    public string $type;

    public ColumnDefinition $definition;

    public string $positionType;

    public ?string $positionColumn;

    public function __construct(string $type, ColumnDefinition $definition)
    {
        $this->type = $type;
        $this->definition = $definition;
        $this->positionColumn = null;
    }

    public function isAdd(): bool
    {
        return $this->type === 'ADD';
    }

    public function isModify(): bool
    {
        return $this->type === 'MODIFY';
    }
}
