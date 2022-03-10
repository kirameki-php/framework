<?php declare(strict_types=1);

namespace Kirameki\Database\Query\Formatters;

use BackedEnum;
use DateTimeInterface;
use Kirameki\Database\Query\Builders\SelectBuilder;
use Kirameki\Database\Query\Statements\ConditionDefinition;
use Kirameki\Database\Query\Support\LockType;
use Kirameki\Database\Query\Support\Operator;
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
    public function formatSelectStatement(SelectStatement $statement): string
    {
        $parts = [];
        $parts[] = $this->formatSelectPart($statement);
        $parts[] = $this->formatFromPart($statement);
        $parts[] = $this->formatWherePart($statement);
        $parts[] = $this->formatGroupByPart($statement);
        $parts[] = $this->formatOrderByPart($statement);
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
    public function getBindingsForSelect(SelectStatement $statement): array
    {
        return $this->getBindingsForConditions($statement);
    }

    /**
     * @param InsertStatement $statement
     * @return string
     */
    public function formatInsertStatement(InsertStatement $statement): string
    {
        $columns = $statement->columns();
        $columnCount = count($columns);
        $listSize = count($statement->dataset);

        $placeholders = [];
        for ($i = 0; $i < $listSize; $i++) {
            $binders = [];
            for ($j = 0; $j < $columnCount; $j++) {
                $binders[] = $this->getParameterMarker();
            }
            $placeholders[] = '(' . implode(', ', $binders) . ')';
        }

        return implode(' ', [
            'INSERT INTO',
            $this->formatTableName($statement->table),
            '(' . implode(', ', Arr::map($columns, fn($c) => $this->addQuotes($c))) . ')',
            'VALUES',
            implode(', ', $placeholders),
        ]);
    }

    /**
     * @param InsertStatement $statement
     * @return array<mixed>
     */
    public function getBindingsForInsert(InsertStatement $statement): array
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
    public function formatUpdateStatement(UpdateStatement $statement): string
    {
        $assignments = [];
        foreach (array_keys($statement->data) as $name) {
            $assignments[] = $this->addQuotes($name) . ' = ?';
        }

        return implode(' ', array_filter([
            'UPDATE',
            $this->formatTableName($statement->table),
            'SET',
            implode(', ', $assignments),
            $this->formatConditionsPart($statement),
        ]));
    }

    /**
     * @param UpdateStatement $statement
     * @return array<mixed>
     */
    public function getBindingsForUpdate(UpdateStatement $statement): array
    {
        return array_merge($statement->data, $this->getBindingsForConditions($statement));
    }

    /**
     * @param DeleteStatement $statement
     * @return string
     */
    public function formatDeleteStatement(DeleteStatement $statement): string
    {
        return implode(' ', array_filter([
            'DELETE FROM',
            $this->formatTableName($statement->table),
            $this->formatConditionsPart($statement),
        ]));
    }

    /**
     * @param DeleteStatement $statement
     * @return array<mixed>
     */
    public function getBindingsForDelete(DeleteStatement $statement): array
    {
        return $this->getBindingsForConditions($statement);
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
    public function formatSelectPart(SelectStatement $statement): string
    {
        $distinct = null;
        if ($statement->distinct) {
            $distinct = 'DISTINCT ';
        }

        if (empty($statement->columns)) {
            $statement->columns[] = '*';
        }

        $expressions = [];
        foreach ($statement->columns as $name) {
            if ($name instanceof Expr) {
                $expressions[] = $name->toSql($this);
                continue;
            }

            /** @var array<string> $segments */
            $segments = preg_split('/\s+as\s+/i', (string) $name);
            if (count($segments) > 1) {
                $expressions[] = $this->formatColumnName($segments[0]) . ' AS ' . $segments[1];
                continue;
            }

            // consists of only alphanumerics so assume it's just a column
            if (ctype_alnum($segments[0])) {
                $expressions[] = $this->formatColumnName($segments[0], $statement->tableAlias);
                continue;
            }

            $expressions[] = $segments[0];
        }

        $lock = null;
        if ($statement->lock !== null) {
            $lock = match ($statement->lock) {
                LockType::Exclusive => 'FOR UPDATE',
                LockType::Shared => 'FOR SHARE',
            };
        }

        return implode(' ', array_filter([
            'SELECT',
            $distinct,
            implode(', ', $expressions),
            $lock,
        ]));
    }

    /**
     * @param BaseStatement $statement
     * @return string
     */
    public function formatFromPart(BaseStatement $statement): string
    {
        if (!isset($statement->table)) {
            return '';
        }
        $expr = $this->formatTableName($statement->table);
        if ($statement->tableAlias !== null) {
            $expr .= ' AS ' . $statement->tableAlias;
        }
        return 'FROM ' . $expr;
    }

    /**
     * @param ConditionsStatement $statement
     * @return string
     */
    public function formatConditionsPart(ConditionsStatement $statement): string
    {
        $parts = [];
        $parts[] = $this->formatWherePart($statement);
        $parts[] = $this->formatOrderByPart($statement);
        if ($statement->limit !== null) {
            $parts[] = 'LIMIT ' . $statement->limit;
        }
        return implode(' ', array_filter($parts));
    }

    /**
     * @param ConditionsStatement $statement
     * @return string
     */
    protected function formatWherePart(ConditionsStatement $statement): string
    {
        if ($statement->where === null) {
            return '';
        }
        $clause = [];
        foreach ($statement->where as $condition) {
            $clause[] = $this->formatCondition($condition, $statement->tableAlias);
        }
        return 'WHERE ' . implode(' AND ', $clause);
    }

    /**
     * @param ConditionDefinition $def
     * @param string|null $table
     * @return string
     */
    public function formatCondition(ConditionDefinition $def, ?string $table): string
    {
        $parts = [];
        $parts[] = $this->formatConditionSegment($def, $table);

        // Dig through all chained clauses if exists
        if ($def->next !== null) {
            $logic = $def->nextLogic;
            while ($def = $def->next) {
                $parts[] = $logic.' '.$this->formatConditionSegment($def, $table);
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
    protected function formatConditionSegment(ConditionDefinition $def, ?string $table): string
    {
        return match ($def->operator) {
            Operator::Raw => $this->formatConditionForRaw($def, $table),
            Operator::Equals => $this->formatConditionForEqual($def, $table),
            Operator::LessThanOrEqualTo => $this->formatConditionForLessThanOrEqualTo($def, $table),
            Operator::LessThan => $this->formatConditionForLessThan($def, $table),
            Operator::GreaterThanOrEqualTo => $this->formatConditionForGreaterThanOrEqualTo($def, $table),
            Operator::GreaterThan => $this->formatConditionForGreaterThan($def, $table),
            Operator::In => $this->formatConditionForIn($def, $table),
            Operator::Between => $this->formatConditionForBetween($def, $table),
            Operator::Exists => $this->formatConditionForExists($def, $table),
            Operator::Like => $this->formatConditionForLike($def, $table),
            Operator::Range => $this->formatConditionForRange($def, $table),
            Operator::Nested => $this->formatConditionForNested($def, $table),
            default => throw new RuntimeException('Unknown Operator: '.Str::valueOf($def->operator?->value)),
        };
    }

    /**
     * @param ConditionDefinition $def
     * @param string|null $table
     * @return string
     */
    protected function formatConditionForRaw(ConditionDefinition $def, ?string $table): string
    {
        if ($def->value instanceof Expr) {
            return $def->value->toSql($this);
        }

        throw new RuntimeException('Unknown condition');
    }

    /**
     * @param ConditionDefinition $def
     * @param string|null $table
     * @return string
     */
    protected function formatConditionForEqual(ConditionDefinition $def, ?string $table): string
    {
        $column = $this->formatColumnName($this->getDefinedColumn($def), $table);
        return $def->value !== null
            ? $column.' '.($def->negated ? '!=': '=').' '.$this->getParameterMarker()
            : $column.' '.($def->negated ? 'IS NOT NULL' : 'IS NULL');
    }

    /**
     * @param ConditionDefinition $def
     * @param string|null $table
     * @return string
     */
    protected function formatConditionForLessThanOrEqualTo(ConditionDefinition $def, ?string $table): string
    {
        $column = $this->formatColumnName($this->getDefinedColumn($def), $table);
        $operator = $def->negated ? '>' : '<=';
        return $column.' '.$operator.' '.$this->getParameterMarker();
    }

    /**
     * @param ConditionDefinition $def
     * @param string|null $table
     * @return string
     */
    protected function formatConditionForLessThan(ConditionDefinition $def, ?string $table): string
    {
        $column = $this->formatColumnName($this->getDefinedColumn($def), $table);
        $operator = $def->negated ? '>=' : '<';
        return $column.' '.$operator.' '.$this->getParameterMarker();
    }

    /**
     * @param ConditionDefinition $def
     * @param string|null $table
     * @return string
     */
    protected function formatConditionForGreaterThanOrEqualTo(ConditionDefinition $def, ?string $table): string
    {
        $column = $this->formatColumnName($this->getDefinedColumn($def), $table);
        $operator = $def->negated ? '<' : '>=';
        return $column.' '.$operator.' '.$this->getParameterMarker();
    }

    /**
     * @param ConditionDefinition $def
     * @param string|null $table
     * @return string
     */
    protected function formatConditionForGreaterThan(ConditionDefinition $def, ?string $table): string
    {
        $column = $this->formatColumnName($this->getDefinedColumn($def), $table);
        $operator = $def->negated ? '<=' : '>';
        return $column.' '.$operator.' '.$this->getParameterMarker();
    }

    /**
     * @param ConditionDefinition $def
     * @param string|null $table
     * @return string
     */
    protected function formatConditionForIn(ConditionDefinition $def, ?string $table): string
    {
        $column = $this->formatColumnName($this->getDefinedColumn($def), $table);
        $operator = $def->negated ? 'NOT IN' : 'IN';
        $value = $def->value;

        if (is_array($value)) {
            if (!empty($value)) {
                $boundNames = array_map(fn() => $this->getParameterMarker(), $value);
                return $column.' '.$operator.' ('.implode(', ', $boundNames).')';
            }
            return '1 = 0';
        }

        if ($value instanceof SelectBuilder) {
            return $column.' '.$operator.' '.$this->formatSubQuery($value);
        }

        throw new RuntimeException('Unknown condition');
    }

    /**
     * @param ConditionDefinition $def
     * @param string|null $table
     * @return string
     */
    protected function formatConditionForBetween(ConditionDefinition $def, ?string $table): string
    {
        $column = $this->formatColumnName($this->getDefinedColumn($def), $table);
        return $column.' '.($def->negated ? 'NOT ' : '').'BETWEEN '.$this->getParameterMarker().' AND '.$this->getParameterMarker();
    }

    /**
     * @param ConditionDefinition $def
     * @param string|null $table
     * @return string
     */
    protected function formatConditionForExists(ConditionDefinition $def, ?string $table): string
    {
        $column = $this->formatColumnName($this->getDefinedColumn($def), $table);
        $operator = $def->negated ? 'NOT EXISTS' : 'EXISTS';
        $value = $def->value;

        if ($value instanceof SelectBuilder) {
            return $column.' '.$operator.' '.$this->formatSubQuery($value);
        }

        throw new RuntimeException('Unknown condition');
    }

    /**
     * @param ConditionDefinition $def
     * @param string|null $table
     * @return string
     */
    protected function formatConditionForLike(ConditionDefinition $def, ?string $table): string
    {
        $column = $this->formatColumnName($this->getDefinedColumn($def), $table);
        $operator = $def->negated ? 'NOT LIKE' : 'LIKE';
        return $column.' '.$operator.' '.$this->getParameterMarker();
    }

    /**
     * @param ConditionDefinition $def
     * @param string|null $table
     * @return string
     */
    protected function formatConditionForRange(ConditionDefinition $def, ?string $table): string
    {
        $column = $this->formatColumnName($this->getDefinedColumn($def), $table);
        $negated = $def->negated;
        $value = $def->value;

        if ($value instanceof Range) {
            $lowerOperator = $negated
                ? ($value->lowerClosed ? '<' : '<=')
                : ($value->lowerClosed ? '>=' : '>');
            $upperOperator = $negated
                ? ($value->upperClosed ? '>' : '>=')
                : ($value->upperClosed ? '<=' : '<');
            $expr = $column.' '.$lowerOperator.' '.$this->getParameterMarker();
            $expr.= $negated ? ' OR ' : ' AND ';
            $expr.= $column.' '.$upperOperator.' '.$this->getParameterMarker();
            return $expr;
        }

        throw new RuntimeException('Unknown condition');
    }

    /**
     * @param ConditionDefinition $def
     * @param string|null $table
     * @return string
     */
    protected function formatConditionForNested(ConditionDefinition $def, ?string $table): string
    {
        if ($def->value instanceof SelectBuilder) {
            return $this->formatSubQuery($def->value);
        }

        throw new RuntimeException('Unknown condition');
    }

    /**
     * @param SelectBuilder $builder
     * @return string
     */
    protected function formatSubQuery(SelectBuilder $builder): string
    {
        return '('.$builder->prepare().')';
    }

    /**
     * @param SelectStatement $statement
     * @return string
     */
    protected function formatGroupByPart(SelectStatement $statement): string
    {
        if ($statement->groupBy === null) {
            return '';
        }
        $clause = [];
        foreach ($statement->groupBy as $column) {
            $clause[] = $this->formatColumnName($column, $statement->tableAlias);
        }
        return 'GROUP BY ' . implode(', ', $clause);
    }

    /**
     * @param ConditionsStatement $statement
     * @return string
     */
    protected function formatOrderByPart(ConditionsStatement $statement): string
    {
        if ($statement->orderBy === null) {
            return '';
        }
        $clause = [];
        foreach ($statement->orderBy as $column => $sort) {
            $clause[] = $this->formatColumnName($column, $statement->tableAlias) . ' ' . $sort;
        }
        return 'ORDER BY ' . implode(', ', $clause);
    }

    /**
     * @param string $name
     * @return string
     */
    public function formatTableName(string $name): string
    {
        return $this->addQuotes($name);
    }

    /**
     * @return string
     */
    public function getParameterMarker(): string
    {
        return '?';
    }

    /**
     * @param string|Expr $name
     * @param string|null $table
     * @return string
     */
    public function formatColumnName(string|Expr $name, ?string $table = null): string
    {
        if ($name instanceof Expr) {
            return $name->toSql($this);
        }

        $name = $name !== '*' ? $this->addQuotes($name) : $name;
        return $table !== null ? $this->formatTableName($table).'.'.$name : $name;
    }

    /**
     * @param string $column
     * @param string $path
     * @param bool $unwrap
     * @return string
     */
    public function formatJsonExtract(string $column, string $path, bool $unwrap): string
    {
        $directive = $unwrap ? '->>' : '->';
        $path = str_starts_with($path, '$.') ? $path : '$.'.$path;
        return $this->formatColumnName($column).$directive.'"'.$path.'"';
    }

    /**
     * @param mixed $value
     * @return mixed
     */
    public function parameterize(mixed $value): mixed
    {
        if (is_iterable($value)) {
            /** @var iterable<array-key, mixed> $value */
            return Json::encode(Arr::from($value));
        }

        if ($value instanceof DateTimeInterface) {
            return '\''.$value->format($this->getDateTimeFormat()).'\'';
        }

        if ($value instanceof BackedEnum) {
            return $value->value;
        }

        return $value;
    }

    /**
     * @return string
     */
    protected function getDateTimeFormat(): string
    {
        return DateTimeInterface::RFC3339_EXTENDED;
    }

    /**
     * @param ConditionDefinition $def
     * @return string
     */
    protected function getDefinedColumn(ConditionDefinition $def): string
    {
        return $def->column ?? throw new RuntimeException('Column name expected but null given');
    }

    /**
     * @param ConditionsStatement $statement
     * @return array<mixed>
     */
    protected function getBindingsForConditions(ConditionsStatement $statement): array
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
     * @param ConditionDefinition $def
     * @return void
     */
    protected function addBindingsForCondition(array &$bindings, ConditionDefinition $def): void
    {
        while ($def !== null) {
            if (is_iterable($def->value)) {
                foreach ($def->value as $binding) {
                    $bindings[] = $binding;
                }
            }
            elseif ($def->value instanceof SelectBuilder) {
                foreach ($def->value->getBindings() as $binding) {
                    $bindings[] = $binding;
                }
            }
            $def = $def->next;
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
