<?php declare(strict_types=1);

namespace Kirameki\Database\Schema\Statements;

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