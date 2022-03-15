<?php declare(strict_types=1);

namespace Kirameki\Database\Query\Formatters;

use BackedEnum;
use DateTimeInterface;
use Kirameki\Database\Query\Builders\SelectBuilder;
use Kirameki\Database\Query\Statements\ConditionDefinition;
use Kirameki\Database\Query\Support\LockOption;
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
use Kirameki\Support\Concerns\Macroable;
use Kirameki\Support\Json;
use Kirameki\Support\Str;
use RuntimeException;
use function array_filter;
use function array_keys;
use function array_map;
use function array_merge;
use function count;
use function current;
use function implode;
use function is_array;
use function is_bool;
use function is_iterable;
use function is_null;
use function is_string;
use function next;
use function preg_replace_callback;
use function str_starts_with;

abstract class Formatter
{
    use Macroable;

    /**
     * @param SelectStatement $statement
     * @return string
     */
    public function formatSelectStatement(SelectStatement $statement): string
    {
        return implode(' ', array_filter([
            $this->formatSelectPart($statement),
            $this->formatFromPart($statement),
            $this->formatWherePart($statement),
            $this->formatGroupByPart($statement),
            $this->formatOrderByPart($statement),
            $this->formatLimitPart($statement),
            $this->formatOffsetPart($statement),
        ]));
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
        return implode(' ', [
            'INSERT INTO',
            $this->quote($statement->table),
            $this->formatInsertColumnsPart($statement),
            'VALUES',
            $this->formatInsertValuesPart($statement),
            $this->formatInsertReturningPart($statement),
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
        return implode(' ', array_filter([
            'UPDATE',
            $this->quote($statement->table),
            'SET',
            $this->formatUpdateAssignmentsPart($statement),
            $this->formatConditionsPart($statement),
        ]));
    }

    /**
     * @param UpdateStatement $statement
     * @return string
     */
    protected function formatUpdateAssignmentsPart(UpdateStatement $statement): string
    {
        $columns = array_keys($statement->data);
        $marker = $this->getParameterMarker();
        $assignments = array_map(fn($column) => $this->columnize($column) . ' = ' . $marker, $columns);
        return $this->asCsv($assignments);
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
            $this->quote($statement->table),
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
                    return $this->literalize($current);
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
    protected function formatSelectPart(SelectStatement $statement): string
    {
        return implode(' ', array_filter([
            'SELECT',
            $statement->distinct ? 'DISTINCT' : null,
            $this->formatSelectColumnsPart($statement),
            $this->formatSelectLockPart($statement),
        ]));
    }

    /**
     * @param SelectStatement $statement
     * @return string
     */
    protected function formatSelectColumnsPart(SelectStatement $statement): string
    {
        $expressions = [];

        if (empty($statement->columns)) {
            $statement->columns[] = '*';
        }

        foreach ($statement->columns as $name) {
            if ($name instanceof Expr) {
                $expressions[] = $name->toSql($this);
                continue;
            }

            $expressions[] = $this->columnize($name, $statement->tableAlias);
        }
        return $this->asCsv($expressions);
    }

    /**
     * @param SelectStatement $statement
     * @return string
     */
    protected function formatSelectLockPart(SelectStatement $statement): string
    {
        return match ($statement->lockType) {
            LockType::Exclusive => 'FOR UPDATE'.$this->formatSelectLockOptionPart($statement),
            LockType::Shared => 'FOR SHARE',
            null => '',
        };
    }

    /**
     * @param SelectStatement $statement
     * @return string
     */
    protected function formatSelectLockOptionPart(SelectStatement $statement): string
    {
        return match ($statement->lockOption) {
            LockOption::Nowait => ' NOWAIT',
            LockOption::SkipLocked => ' SKIP LOCKED',
            null => '',
        };
    }

    /**
     * @param BaseStatement $statement
     * @return string
     */
    protected function formatFromPart(BaseStatement $statement): string
    {
        if (!isset($statement->table)) {
            return '';
        }
        $expr = $this->quote($statement->table);
        if ($statement->tableAlias !== null) {
            $expr .= ' AS ' . $statement->tableAlias;
        }
        return 'FROM ' . $expr;
    }

    /**
     * @param InsertStatement $statement
     * @return string
     */
    protected function formatInsertColumnsPart(InsertStatement $statement): string
    {
        $columns = array_map(fn($column) => $this->columnize($column), $statement->columns());
        return '(' . $this->asCsv($columns) . ')';
    }

    /**
     * @param InsertStatement $statement
     * @return string
     */
    protected function formatInsertValuesPart(InsertStatement $statement): string
    {
        $listSize = count($statement->dataset);
        $columnCount = count($statement->columns());
        $marker = $this->getParameterMarker();
        $placeholders = [];
        for ($i = 0; $i < $listSize; $i++) {
            $binders = [];
            for ($j = 0; $j < $columnCount; $j++) {
                $binders[] = $marker;
            }
            $placeholders[] = '(' . $this->asCsv($binders) . ')';
        }
        return $this->asCsv($placeholders);
    }

    /**
     * @param InsertStatement $statement
     * @return string
     */
    protected function formatInsertReturningPart(InsertStatement $statement): string
    {
        if ($statement->returningColumns === null) {
            return '';
        }

        $columns = array_map(fn($column) => $this->columnize($column), $statement->returningColumns);
        return 'RETURNING ' . $this->asCsv($columns);
    }

    /**
     * @param ConditionsStatement $statement
     * @return string
     */
    public function formatConditionsPart(ConditionsStatement $statement): string
    {
        return implode(' ', array_filter([
            $this->formatWherePart($statement),
            $this->formatOrderByPart($statement),
            $this->formatLimitPart($statement),
        ]));
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
                $parts[] = $logic . ' ' . $this->formatConditionSegment($def, $table);
                $logic = $def->nextLogic;
            }
        }

        return (count($parts) > 1) ? '(' . implode(' ', $parts) . ')' : $parts[0];
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
        $column = $this->columnize($this->getDefinedColumn($def), $table);
        return $def->value !== null
            ? $column . ' ' . ($def->negated ? '!=': '=') . ' ' . $this->getParameterMarker()
            : $column . ' ' . ($def->negated ? 'IS NOT NULL' : 'IS NULL');
    }

    /**
     * @param ConditionDefinition $def
     * @param string|null $table
     * @return string
     */
    protected function formatConditionForLessThanOrEqualTo(ConditionDefinition $def, ?string $table): string
    {
        $column = $this->columnize($this->getDefinedColumn($def), $table);
        $operator = $def->negated ? '>' : '<=';
        return $column . ' ' . $operator . ' ' . $this->getParameterMarker();
    }

    /**
     * @param ConditionDefinition $def
     * @param string|null $table
     * @return string
     */
    protected function formatConditionForLessThan(ConditionDefinition $def, ?string $table): string
    {
        $column = $this->columnize($this->getDefinedColumn($def), $table);
        $operator = $def->negated ? '>=' : '<';
        return $column . ' ' . $operator.' ' . $this->getParameterMarker();
    }

    /**
     * @param ConditionDefinition $def
     * @param string|null $table
     * @return string
     */
    protected function formatConditionForGreaterThanOrEqualTo(ConditionDefinition $def, ?string $table): string
    {
        $column = $this->columnize($this->getDefinedColumn($def), $table);
        $operator = $def->negated ? '<' : '>=';
        return $column . ' ' . $operator . ' ' . $this->getParameterMarker();
    }

    /**
     * @param ConditionDefinition $def
     * @param string|null $table
     * @return string
     */
    protected function formatConditionForGreaterThan(ConditionDefinition $def, ?string $table): string
    {
        $column = $this->columnize($this->getDefinedColumn($def), $table);
        $operator = $def->negated ? '<=' : '>';
        return $column . ' ' . $operator . ' ' . $this->getParameterMarker();
    }

    /**
     * @param ConditionDefinition $def
     * @param string|null $table
     * @return string
     */
    protected function formatConditionForIn(ConditionDefinition $def, ?string $table): string
    {
        $column = $this->columnize($this->getDefinedColumn($def), $table);
        $operator = $def->negated ? 'NOT IN' : 'IN';
        $value = $def->value;

        if (is_array($value)) {
            if (!empty($value)) {
                $boundNames = array_map(fn() => $this->getParameterMarker(), $value);
                return $column . ' ' . $operator . ' (' . $this->asCsv($boundNames) . ')';
            }
            return '1 = 0';
        }

        if ($value instanceof SelectBuilder) {
            return $column . ' ' . $operator . ' ' . $this->formatSubQuery($value);
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
        $column = $this->columnize($this->getDefinedColumn($def), $table);
        $marker = $this->getParameterMarker();
        return $column . ' ' . ($def->negated ? 'NOT ' : '') . 'BETWEEN ' . $marker . ' AND ' . $marker;
    }

    /**
     * @param ConditionDefinition $def
     * @param string|null $table
     * @return string
     */
    protected function formatConditionForExists(ConditionDefinition $def, ?string $table): string
    {
        $column = $this->columnize($this->getDefinedColumn($def), $table);
        $operator = $def->negated ? 'NOT EXISTS' : 'EXISTS';
        $value = $def->value;

        if ($value instanceof SelectBuilder) {
            return $column . ' ' . $operator . ' ' . $this->formatSubQuery($value);
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
        $column = $this->columnize($this->getDefinedColumn($def), $table);
        $operator = $def->negated ? 'NOT LIKE' : 'LIKE';
        return $column . ' ' . $operator . ' ' . $this->getParameterMarker();
    }

    /**
     * @param ConditionDefinition $def
     * @param string|null $table
     * @return string
     */
    protected function formatConditionForRange(ConditionDefinition $def, ?string $table): string
    {
        $column = $this->columnize($this->getDefinedColumn($def), $table);
        $negated = $def->negated;
        $value = $def->value;
        $marker = $this->getParameterMarker();

        if ($value instanceof Range) {
            $lowerOperator = $negated
                ? ($value->lowerClosed ? '<' : '<=')
                : ($value->lowerClosed ? '>=' : '>');
            $upperOperator = $negated
                ? ($value->upperClosed ? '>' : '>=')
                : ($value->upperClosed ? '<=' : '<');
            $expr = $column . ' ' . $lowerOperator . ' ' . $marker;
            $expr.= $negated ? ' OR ' : ' AND ';
            $expr.= $column . ' ' . $upperOperator . ' ' . $marker;
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
            $clause[] = $this->columnize($column, $statement->tableAlias);
        }
        return 'GROUP BY ' . $this->asCsv($clause);
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
            $clause[] = $this->columnize($column, $statement->tableAlias) . ' ' . $sort;
        }
        return 'ORDER BY ' . $this->asCsv($clause);
    }

    /**
     * @param ConditionsStatement $statement
     * @return string
     */
    protected function formatLimitPart(ConditionsStatement $statement): string
    {
        return $statement->limit !== null
            ? 'LIMIT ' . $statement->limit
            : '';
    }

    /**
     * @param SelectStatement $statement
     * @return string
     */
    protected function formatOffsetPart(SelectStatement $statement): string
    {
        return $statement->offset !== null
            ? 'OFFSET ' . $statement->offset
            : '';
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
        return $this->columnize($column) . $directive . '"' . $path . '"';
    }

    /**
     * @param string|Expr $name
     * @param string|null $table
     * @return string
     */
    public function columnize(string|Expr $name, ?string $table = null): string
    {
        if ($name instanceof Expr) {
            return $name->toSql($this);
        }

        $name = $name !== '*' ? $this->quote($name) : $name;
        return $table !== null ? $this->quote($table) . '.' . $name : $name;
    }

    /**
     * @param string $str
     * @return string
     */
    public function quote(string $str): string
    {
        $char = '`';
        return $char . str_replace($char, $char . $char, $str) . $char;
    }

    /**
     * @param string $str
     * @return string
     */
    public function literalize(string $str): string
    {
        $char = '\'';
        return $char . str_replace($char, $char . $char, $str) . $char;
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
            return $this->literalize($value->format($this->getDateTimeFormat()));
        }

        if ($value instanceof BackedEnum) {
            return $value->value;
        }

        return $value;
    }

    /**
     * @return string
     */
    public function getParameterMarker(): string
    {
        return '?';
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
     * @param array<scalar> $values
     * @return string
     */
    protected function asCsv(array $values): string
    {
        return implode(', ', $values);
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
}
