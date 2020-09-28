<?php

namespace Kirameki\Database\Schema\Statements;

class ColumnDefinition
{
    public string $name;

    public string $type;

    public ?int $size;

    public ?int $scale;

    public ?bool $primaryKey;

    public ?bool $nullable;

    public ?bool $autoIncrement;

    public ?string $comment;

    public $default;

    public function __construct(string $name, string $type)
    {
        $this->name = $name;
        $this->type = $type;
        $this->size = null;
        $this->scale = null;
        $this->primaryKey = null;
        $this->nullable = true;
        $this->autoIncrement = null;
        $this->comment = null;
        $this->default = null;
    }
}
