<?php declare(strict_types=1);

namespace Kirameki\Database\Query\Statements;

abstract class ConditionsStatement extends BaseStatement
{
    /**
     * @var array<ConditionDefinition>|null
     */
    public ?array $where = null;

    /**
     * @var array<string, string>|null
     */
    public ?array $orderBy = null;

    /**
     * @var int|null
     */
    public ?int $limit = null;

    /**
     * @return void
     */
    public function __clone()
    {
        if ($this->where !== null) {
            $where = [];
            foreach ($this->where as $condition) {
                $where[] = clone $condition;
            }
            $this->where = $where;
        }
    }
}
