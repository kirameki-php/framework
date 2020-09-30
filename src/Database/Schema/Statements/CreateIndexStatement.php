<?php


namespace Kirameki\Database\Schema\Statements;


class CreateIndexStatement extends Statement
{
    /**
     * @var string|null
     */
    public ?string $name;

    /**
     * @var string[]
     */
    public array $columns;

    /**
     * @var bool
     */
    public ?bool $unique;

    /**
     * @var string
     */
    public string $order;

    /**
     * @var string|null
     */
    public ?string $comment;

    /**
     * @param string|null $table
     * @param string[] $columns
     */
    public function __construct(?string $table, array $columns)
    {
        parent::__construct($table);
        $this->name = null;
        $this->columns = $columns;
        $this->unique = null;
        $this->order = 'ASC';
        $this->comment = null;
    }
}
