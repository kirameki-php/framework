<?php

namespace Kirameki\Database\Schema\Statements;

use Kirameki\Database\Schema\Builders\ColumnBuilder;
use Kirameki\Database\Schema\Builders\CreateIndexBuilder;

class AlterTableStatement extends BaseStatement
{
    /**
     * @var array
     */
    public array $actions;

    /**
     * @param string $table
     */
    public function __construct(string $table)
    {
        parent::__construct($table);
    }

    /**
     * @param $action
     */
    public function addAction($action)
    {
        $this->actions[] = $action;
    }
}