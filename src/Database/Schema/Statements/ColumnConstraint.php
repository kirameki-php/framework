<?php

namespace Kirameki\Database\Schema\Statements;

class ColumnConstraint
{
    public ?int $size;

    public ?int $scale;

    public ?bool $primaryKey;

    public ?bool $nullable;

    public ?bool $autoIncrement;

    public ?string $comment;

    public $default;

    public function __construct()
    {
        $this->size = null;
        $this->scale = null;
        $this->primaryKey = null;
        $this->nullable = true;
        $this->autoIncrement = null;
        $this->comment = null;
        $this->default = null;
    }
}
