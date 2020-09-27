<?php

namespace Kirameki\Database\Schema\Builders;

use Kirameki\Database\Schema\Formatters\Formatter;
use Kirameki\Database\Schema\Statements\ColumnConstraint;

class Column
{
    public string $name;

    public string $type;

    /**
     * @var ColumnConstraint
     */
    protected ColumnConstraint $constraint;

    /**
     * @param string $name
     * @param string $type
     * @param int|null $size
     * @return static
     */
    public static function sizeable(string $name, string $type, ?int $size)
    {
        $instance = new static($name, $type);
        $instance->constraint->size = $size;
        return $instance;
    }

    /**
     * @param string $name
     * @param string $type
     * @param int|null $precision
     * @param int|null $scale
     * @return static
     */
    public static function scalable(string $name, string $type, ?int $precision, ?int $scale)
    {
        $instance = new static($name, $type);
        $instance->constraint->size = $precision;
        $instance->constraint->scale = $scale;
        return $instance;
    }

    /**
     * @param string $name
     * @param string $type
     */
    public function __construct(string $name, string $type)
    {
        $this->name = $name;
        $this->type = $type;
        $this->constraint = new ColumnConstraint();
    }

    /**
     * @param bool $toggle
     * @return $this
     */
    public function primaryKey(bool $toggle = true)
    {
        $this->constraint->primaryKey = $toggle;
        return $this;
    }

    /**
     * @return $this
     */
    public function notNull()
    {
        $this->constraint->nullable = false;
        return $this;
    }

    /**
     * @param bool $toggle
     * @return $this
     */
    public function autoIncrement(bool $toggle = true)
    {
        $this->constraint->autoIncrement = $toggle;
        return $this;
    }

    /**
     * @param string $comment
     * @return $this
     */
    public function comment(string $comment)
    {
        $this->constraint->comment = $comment;
        return $this;
    }

    /**
     * @param $value
     * @return $this
     */
    public function default($value)
    {
        $this->constraint->default = $value;
        return $this;
    }

    /**
     * @param Formatter $formatter
     * @return string
     */
    public function toSql(Formatter $formatter)
    {
        return $formatter->column($this->name, $this->type, $this->constraint);
    }
}
