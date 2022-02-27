<?php declare(strict_types=1);

namespace Kirameki\Database\Query\Formatters;

use DateTimeInterface;
use Kirameki\Database\Query\Statements\ConditionDefinition;
use Kirameki\Database\Query\Support\Range;
use Kirameki\Database\Support\Expr;
use Kirameki\Database\Query\Statements\BaseStatement;
use Kirameki\Database\Query\Statements\ConditionsStatement;
use Kirameki\Database\Query\Statements\DeleteStatement;
use Kirameki\Database\Query\Statements\InsertStatement;
use Kirameki\Database\Query\Statements\SelectStatement;
use Kirameki\Database\Query\Statements\UpdateStatement;
use Kirameki\Support\Arr;
use Kirameki\Support\Json;
use Kirameki\Support\Str;
use RuntimeException;

class Formatter
{
    protected string $quote = '`';

    /**
     * @param SelectStatement $statement
     * @return string
     */
    public function selectStatement(SelectStatement $statement): string
    {
        $parts = [];
        $parts[] = $this->selectPart($statement);
        $parts[] = $this->fromPart($statement);
        $parts[] = $this->wherePart($statement);
        $parts[] = $this->groupByPart($statement);
        $parts[] = $this->orderByPart($statement);
        if ($statement->limit !== null) {
            $parts[] = 'LIMIT ' . $statement->limit;
        }
        if ($statement->offset !== null) {
            $parts[] = 'OFFSET ' . $statement->offset;
        }
        return implode(' ', array_filter($parts));
    }

    /**
     * @param SelectStatement $statement
     * @return array<mixed>
     */
    public function selectBindings(SelectStatement $statement): array
    {
        return $this->bindingsForConditions($statement);
    }

    /**
     * @param InsertStatement $statement
     * @return string
     */
    public function insertStatement(InsertStatement $statement): string
    {
        $columns = $statement->columns();
        $columnCount = count($columns);
        $listSize = count($statement->dataset);

        $placeholders = [];
        for ($i = 0; $i < $listSize; $i++) {
            $binders = [];
            for ($j = 0; $j < $columnCount; $j++) {
                $binders[] = $this->bindName();
            }
            $placeholders[] = '(' . implode(', ', $binders) . ')';
        }

        return implode(' ', [
            'INSERT INTO',
            $this->tableName($statement->table),
            '(' . implode(', ', Arr::map($columns, fn($c) => $this->addQuotes($c))) . ')',
            'VALUES',
            implode(', ', $placeholders),
        ]);
    }

    /**
     * @param InsertStatement $statement
     * @return array<mixed>
     */
    public function insertBindings(InsertStatement $statement): array
    {
        $columns = $statement->columns();

        $bindings = [];
        foreach ($statement->dataset as $data) {
            if (!is_array($data)) {
                throw new RuntimeException('Data should be an array but ' . Str::typeOf($data) . ' given.');
            }
            foreach ($columns as $column) {
                $bindings[] = $data[$column] ?? null;
            }
        }
        return $bindings;
    }

    /**
     * @param UpdateStatement $statement
     * @return string
     */
    public function updateStatement(UpdateStatement $statement): string
    {
        $assignments = [];
        foreach (array_keys($statement->data) as $name) {
            $assignments[] = $this->addQuotes($name) . ' = ?';
        }

        return implode(' ', array_filter([
            'UPDATE',
            $this->tableName($statement->table),
            'SET',
            implode(', ', $assignments),
            $this->conditionsPart($statement),
        ]));
    }

    /**
     * @param UpdateStatement $statement
     * @return array<mixed>
     */
    public function updateBindings(UpdateStatement $statement): array
    {
        return array_merge($statement->data, $this->bindingsForConditions($statement));
    }

    /**
     * @param DeleteStatement $statement
     * @return string
     */
    public function deleteStatement(DeleteStatement $statement): string
    {
        return implode(' ', array_filter([
            'DELETE FROM',
            $this->tableName($statement->table),
            $this->conditionsPart($statement),
        ]));
    }

    /**
     * @param DeleteStatement $statement
     * @return array<mixed>
     */
    public function deleteBindings(DeleteStatement $statement): array
    {
        return $this->bindingsForConditions($statement);
    }

    /**
     * FOR DEBUGGING ONLY
     *
     * @param string $statement
     * @param array<mixed> $bindings
     * @return string
     */
    public function interpolate(string $statement, array $bindings): string
    {
        $remains = count($bindings);
        return (string) preg_replace_callback('/\?\??/', function ($matches) use (&$bindings, &$remains) {
            if ($matches[0] === '?' && $remains > 0) {
                $current = current($bindings);
                next($bindings);
                $remains--;

                if (is_null($current)) {
                    return 'NULL';
                }

                if (is_bool($current)) {
                    return $current ? 'TRUE' : 'FALSE';
                }

                if (is_string($current)) {
                    return $this->toStringLiteral($current);
                }

                return $this->parameterize($current);
            }

            return $matches[0];
        }, $statement);
    }

    /**
     * @param SelectStatement $statement
     * @return string
     */
    public function selectPart(SelectStatement $statement): string
    {
        if (empty($statement->columns)) {
            $statement->columns[] = '*';
        }

        $expressions = [];

        $distinct = '';
        if ($statement->distinct) {
            $distinct = 'DISTINCT ';
        }

        foreach ($statement->columns as $name) {
            if ($name instanceof Expr) {
                $expressions[] = $name->toString();
                continue;
            }

            /** @var array<string> $segments */
            $segments = preg_split('/\s+as\s+/i', (string) $name);
            if (count($segments) > 1) {
                $expressions[] = $this->columnName($segments[0]) . ' AS ' . $segments[1];
                continue;
            }

            // consists of only alphanumerics so assume it's just a column
            if (ctype_alnum($segments[0])) {
                $expressions[] = $this->columnName($segments[0], $statement->tableAlias);
                continue;
            }

            $expressions[] = $segments[0];
        }
        return 'SELECT ' . $distinct . implode(', ', $expressions);
    }

    /**
     * @param BaseStatement $statement
     * @return string
     */
    public function fromPart(BaseStatement $statement): string
    {
        if (!isset($statement->table)) {
            return '';
        }
        $expr = $this->tableName($statement->table);
        if ($statement->tableAlias !== null) {
            $expr .= ' AS ' . $statement->tableAlias;
        }
        return 'FROM ' . $expr;
    }

    /**
     * @param ConditionsStatement $statement
     * @return string
     */
    public function conditionsPart(ConditionsStatement $statement): string
    {
        $parts = [];
        $parts[] = $this->wherePart($statement);
        $parts[] = $this->orderByPart($statement);
        if ($statement->limit !== null) {
            $parts[] = 'LIMIT ' . $statement->limit;
        }
        return implode(' ', array_filter($parts));
    }

    /**
     * @param ConditionDefinition $def
     * @param string|null $table
     * @return string
     */
    public function condition(ConditionDefinition $def, ?string $table): string
    {
        $parts = [];
        $parts[] = $this->conditionSegment($def, $table);

        // Dig through all chained clauses if exists
        if ($def->next !== null) {
            $logic = $def->nextLogic;
            while ($def = $def->next) {
                $parts[] = $logic.' '.$this->conditionSegment($def, $table);
                $logic = $def->nextLogic;
            }
        }

        return (count($parts) > 1) ? '('.implode(' ', $parts).')': $parts[0];
    }

    /**
     * @param ConditionDefinition $def
     * @param string|null $table
     * @return string
     */
    protected function conditionSegment(ConditionDefinition $def, ?string $table): string
    {
        // Handles raw expression
        if ($def->column === null) {
            return $def->column.' '.$def->value;
        }

        $column = $this->columnName($def->column, $table);
        $operator = $def->operator;
        $negated = $def->negated;
        $value = $def->value;

        // Handles nested condition
        if ($operator === null && $value instanceof ConditionDefinition) {
            return $this->condition($value, null);
        }

        if ($operator === '=') {
            if ($value === null) {
                $expr = $negated ? 'IS NOT NULL' : 'IS NULL';
                return $column.' '.$expr;
            }
            $operator = $negated ? '!'.$operator : $operator;
            return $column.' '.$operator.' '.$this->bindName();
        }

        if ($operator === 'IN' && is_array($value)) {
            if (empty($value)) {
                return '1 = 0';
            }
            $operator = $negated ? 'NOT '.$operator : $operator;
            $bindNames = [];
            for($i = 0, $size = count($value); $i < $size; $i++) {
                $bindNames[] = $this->bindName();
            }
            return $column.' '.$operator.' ('.implode(', ', $bindNames).')';
        }

        if ($operator === 'BETWEEN') {
            $operator = $negated ? 'NOT '.$operator : $operator;
            return $column.' '.$operator.' '.$this->bindName().' AND '.$this->bindName();
        }

        if ($operator === 'LIKE') {
            $operator = $negated ? 'NOT '.$operator : $operator;
            return $column.' '.$operator.' '.$this->bindName();
        }

        if ($operator === 'RANGE' && $value instanceof Range) {
            $lowerOperator = $negated
                ? ($value->lowerClosed ? '<' : '<=')
                : ($value->lowerClosed ? '>=' : '>');
            $upperOperator = $negated
                ? ($value->upperClosed ? '>' : '>=')
                : ($value->upperClosed ? '<=' : '<');
            $expr = $column.' '.$lowerOperator.' '.$this->bindName();
            $expr.= $negated ? ' OR ' : ' AND ';
            $expr.= $column.' '.$upperOperator.' '.$this->bindName();
            return $expr;
        }

        // ">", ">=", "<", "<=" and raw cannot be negated
        if ($negated) {
            // TODO needs better exception
            throw new RuntimeException("Negation not valid for '$operator'");
        }

        if (is_array($value) && count($value) > 1) {
            throw new RuntimeException(count($value).' parameters for condition detected where only 1 is expected.');
        }

        return $column.' '.$operator.' '.$this->bindName();
    }

    /**
     * @param ConditionsStatement $statement
     * @return string
     */
    protected function wherePart(ConditionsStatement $statement): string
    {
        if ($statement->where === null) {
            return '';
        }
        $clause = [];
        foreach ($statement->where as $condition) {
            $clause[] = $this->condition($condition, $statement->tableAlias);
        }
        return 'WHERE ' . implode(' AND ', $clause);
    }

    /**
     * @param SelectStatement $statement
     * @return string
     */
    protected function groupByPart(SelectStatement $statement): string
    {
        if ($statement->groupBy === null) {
            return '';
        }
        $clause = [];
        foreach ($statement->groupBy as $column) {
            $clause[] = $this->columnName($column, $statement->tableAlias);
        }
        return 'GROUP BY ' . implode(', ', $clause);
    }

    /**
     * @param ConditionsStatement $statement
     * @return string
     */
    protected function orderByPart(ConditionsStatement $statement): string
    {
        if ($statement->orderBy === null) {
            return '';
        }
        $clause = [];
        foreach ($statement->orderBy as $column => $sort) {
            $clause[] = $this->columnName($column, $statement->tableAlias) . ' ' . $sort;
        }
        return 'ORDER BY ' . implode(', ', $clause);
    }

    /**
     * @param string $name
     * @return string
     */
    public function tableName(string $name): string
    {
        return $this->addQuotes($name);
    }

    /**
     * @return string
     */
    public function bindName(): string
    {
        return '?';
    }

    /**
     * @param string $name
     * @param string|null $table
     * @return string
     */
    public function columnName(string $name, ?string $table = null): string
    {
        $name = $name !== '*' ? $this->addQuotes($name) : $name;
        return $table !== null ? $this->tableName($table).'.'.$name : $name;
    }

    /**
     * @param mixed $value
     * @return mixed
     */
    public function parameterize(mixed $value): mixed
    {
        if (is_iterable($value)) {
            return Json::encode(Arr::from($value));
        }

        if ($value instanceof DateTimeInterface) {
            return '\''.$value->format(DateTimeInterface::RFC3339_EXTENDED).'\'';
        }

        return $value;
    }

    /**
     * @param ConditionsStatement $statement
     * @return array<mixed>
     */
    protected function bindingsForConditions(ConditionsStatement $statement): array
    {
        $bindings = [];
        if ($statement->where !== null) {
            foreach ($statement->where as $cond) {
                $this->addBindingsForCondition($bindings, $cond);
            }
        }
        return $bindings;
    }

    /**
     * @param array<int, mixed> $bindings
     * @param ConditionDefinition $cond
     * @return void
     */
    protected function addBindingsForCondition(array &$bindings, ConditionDefinition $cond): void
    {
        while ($cond !== null) {
            if ($cond->value instanceof Expr) {
                // Expressions are evaluated as raw string so it has no parameter
            }
            elseif ($cond->value instanceof ConditionDefinition) {
                $this->addBindingsForCondition($bindings, $cond->value);
            }
            elseif (is_iterable($cond->value)) {
                foreach ($cond->value as $binding) {
                    $bindings[] = $binding;
                }
            }
            else {
                $bindings[] = $cond->value;
            }

            $cond = $cond->next;
        }

    }

    /**
     * @param string $text
     * @return string
     */
    protected function addQuotes(string $text): string
    {
        $quoted = $this->quote;
        $quoted.= str_replace($this->quote, $this->quote.$this->quote, $text);
        $quoted.= $this->quote;
        return $quoted;
    }

    /**
     * @param string $str
     * @return string
     */
    protected function toStringLiteral(string $str): string
    {
        return "'".str_replace("'", "''", $str)."'";
    }
}
