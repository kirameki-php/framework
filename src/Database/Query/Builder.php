<?php

namespace Kirameki\Database\Query;

use Closure;
use Kirameki\Support\Collection;
use PDO;
use RuntimeException;

class Builder
{
    /**
     * @var PDO
     */
    protected PDO $pdo;

    /**
     * @var Formatter
     */
    protected Formatter $formatter;

    /**
     * @var string
     */
    public string $from;

    /**
     * @var string|null
     */
    public ?string $as;

    /**
     * @var bool
     */
    public bool $distinct = false;

    /**
     * @var array|null
     */
    public ?array $select;

    /**
     * @var WhereClause[]
     */
    public ?array $wheres;

    /**
     * @var array|null
     */
    public ?array $order;

    /**
     * @var int|null
     */
    public ?int $limit;

    /**
     * @var int|null
     */
    public ?int $offset;

    /**
     * @var bool|null
     */
    public ?bool $lock;

    /**
     * Do a deep clone of object types
     * @return void
     */
    public function __clone()
    {
        $this->wheres = array_map(static fn($w) => clone $w, $this->wheres);
    }

    /**
     * @param string $table
     * @param string|null $as
     * @return $this
     */
    public function from(string $table, ?string $as = null)
    {
        $this->from = $table;
        $this->as = $as;
        return $this;
    }

    /**
     * @param mixed ...$columns
     * @return $this
     */
    public function select(...$columns)
    {
        $this->select = $columns;
        return $this;
    }

    /**
     * @return $this
     */
    public function distinct()
    {
        $this->distinct = true;
        return $this;
    }

    /**
     * @param $column
     * @param mixed|null $operator
     * @param mixed|null $value
     * @return $this
     */
    public function where($column, $operator = null, $value = null)
    {
        $num = func_num_args();

        if ($num === 1) {
            return is_callable($column)
                ? $this->addWhereClause(WhereClause::for($this, $column)->tap($column))
                : $this->addWhereClause($column);
        }

        if ($num === 2) {
            return is_array($operator)
                ? $this->addWhereClause(WhereClause::for($this, $column)->in($operator))
                : $this->addWhereClause(WhereClause::for($this, $column)->eq($operator));
        }

        if ($num === 3) {
            return $this->addWhereClause(WhereClause::for($this, $column)->with($operator, $value));
        }

        throw new \RuntimeException('Invalid number of arguments. expected: 1~3. '.$num.' given.');
    }

    /**
     * @param string $raw
     * @return $this
     */
    public function whereRaw(string $raw)
    {
        return $this->addWhereClause(WhereClause::raw($this, $raw));
    }

    /**
     * @param array $pairs
     * @return $this
     */
    public function order(array $pairs)
    {
        foreach ($pairs as $column => $sort) {
            $this->orderBy($column, $sort);
        }
        return $this;
    }

    /**
     * @param string $column
     * @param string $sort
     * @return $this
     */
    public function orderBy(string $column, string $sort = 'ASC')
    {
        $sort = strtoupper($sort);
        if (! in_array($sort, ['ASC', 'DESC'])) {
            throw new RuntimeException('Invalid sorting: '.$sort. ' Only ASC or DESC is allowed.');
        }
        $this->order ??= [];
        $this->order[$column] = $sort;
        return $this;
    }

    /**
     * @param string $column
     * @return $this
     */
    public function orderByAsc(string $column)
    {
        return $this->orderBy($column, 'ASC');
    }

    /**
     * @param string $column
     * @return $this
     */
    public function orderByDesc(string $column)
    {
        return $this->orderBy($column, 'DESC');
    }

    /**
     * @return $this
     */
    public function reorder()
    {
        $this->order = null;
        return $this;
    }

    /**
     * @param int $count
     * @return $this
     */
    public function limit(int $count)
    {
        $this->limit = $count;
        return $this;
    }

    /**
     * @param int $skipRows
     * @return $this
     */
    public function offset(int $skipRows)
    {
        $this->offset = $skipRows;
        return $this;
    }

    /**
     * @return $this
     */
    public function lock()
    {
        $this->lock = true;
        return $this;
    }

    // Terminal Methods -------------------------------------------------------

    /**
     * @return Collection
     */
    public function all()
    {
        $statement = $this->pdo->prepare($this->toSql());
        $statement->execute($this->getBindings());
        return new Collection();
    }

    /**
     * @return array|int
     */
    public function count(): int|array
    {
        return $this->select('count(*) as cnt')->all();
    }

    /**
     * @param WhereClause $clause
     * @return $this
     */
    protected function addWhereClause(WhereClause $clause)
    {
        $this->wheres ??= [];
        $this->wheres[] = $clause;
        return $this;
    }

    /**
     * @return Formatter
     */
    public function getFormatter(): Formatter
    {
        return $this->formatter ??= new Formatter($this->pdo);
    }

    /**
     * @return array
     */
    public function getBindings(): array
    {
        $bindings = [];
        foreach ($this->wheres as $where) {
            $whereClauses[] = $where->toSql();
            foreach($where->getBindings() as $binding) {
                $bindings[] = $binding;
            }
        }
        return $bindings;
    }

    /**
     * @param false $bind
     * @return string
     */
    public function toSql($bind = false): string
    {
        $formatter = $this->getFormatter();

        $exprs = [];

        if (empty($this->select)) {
            $this->select = ['*'];
        }
        $exprs[] = implode(', ', $this->select);

        $whereClauses = array_map(static fn(WhereClause $w) => $w->toSql(), $this->wheres);
        $exprs[] = 'WHERE '.implode(' AND ', $whereClauses);

        if ($this->offset !== null) {
            $exprs[] = 'OFFSET '.$this->offset;
        }

        if ($this->limit !== null) {
            $exprs[] = 'LIMIT '.$this->limit;
        }

        if (!empty($this->order)) {
            $exprs[] = $formatter->order($this->order, $this->from);
        }

        $sql = implode(' ', $exprs);

        if ($bind) {
            $bindings = $this->getBindings();
            $sql = preg_replace_callback('/\?\??/', static function($matches) use (&$bindings) {
                if ($matches[0] === '?') {
                    $current = current($bindings);
                    next($bindings);
                    return $current;
                }
                return $matches[0];
            }, $sql);
        }

        return $sql;
    }
}
