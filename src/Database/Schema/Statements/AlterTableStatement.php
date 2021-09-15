<?php declare(strict_types=1);

namespace Kirameki\Database\Schema\Statements;

class AlterTableStatement extends BaseStatement
{
    /**
     * @var array
     */
    public array $actions;

    /**
     * @param $action
     */
    public function addAction($action): void
    {
        $this->actions[] = $action;
    }
}