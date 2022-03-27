<?php declare(strict_types=1);

namespace Kirameki\Database\Query\Builders;

use Kirameki\Database\Query\Statements\JoinDefinition;
use Kirameki\Database\Query\Support\JoinType;
use Webmozart\Assert\Assert;

class JoinBuilder
{
    /**
     * @var JoinDefinition
     */
    protected JoinDefinition $definition;

    /**
     * @var ConditionBuilder
     */
    protected ConditionBuilder $condition;

    /**
     * @param JoinType $type
     * @param string $table
     */
    public function __construct(JoinType $type, string $table)
    {
        $this->definition = new JoinDefinition($type, $table);
    }

    /**
     * @param mixed ...$args
     * @return $this
     */
    public function on(mixed ...$args): static
    {
        Assert::countBetween($args, 1, 3);
        $this->condition = $this->buildCondition(...$args);
        $this->definition->on = $this->condition->getDefinition();
        return $this;
    }

    /**
     * @param mixed ...$args
     * @return $this
     */
    public function and(mixed ...$args): static
    {
        Assert::countBetween($args, 1, 3);
        $this->condition->and()->apply($this->buildCondition(...$args));
        return $this;
    }

    /**
     * @param mixed ...$args
     * @return $this
     */
    public function or(mixed ...$args): static
    {
        Assert::countBetween($args, 1, 3);
        $this->condition->or()->apply($this->buildCondition(...$args));
        return $this;
    }

    /**
     * @param mixed ...$args
     * @return ConditionBuilder
     */
    protected function buildCondition(mixed ...$args): ConditionBuilder
    {
        return ConditionBuilder::fromArgs(...$args);
    }

    /**
     * @return JoinDefinition
     */
    public function getDefinition(): JoinDefinition
    {
        return $this->definition;
    }
}
