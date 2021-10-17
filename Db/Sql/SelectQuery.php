<?php

/*
 * This file is part of the VV package.
 *
 * (c) Volodymyr Sarnytskyi <v00v4n@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace VV\Db\Sql;

use VV\Db\Model\Table;
use VV\Db\Result;
use VV\Db\Sql;
use VV\Db\Sql\Clauses\ColumnsClause;
use VV\Db\Sql\Clauses\CombiningClause;
use VV\Db\Sql\Clauses\GroupByClause;
use VV\Db\Sql\Clauses\LimitClause;
use VV\Db\Sql\Clauses\OrderByClause;
use VV\Db\Sql\Clauses\QueryWhereTrait;
use VV\Db\Sql\Clauses\TableClause;
use VV\Db\Sql\Expressions\DbObject;
use VV\Db\Sql\Expressions\Expression;
use VV\Db\Sql\Predicates\Predicate;

/**
 * Class SelectQuery
 *
 * @package VV\Db\Sql
 *
 * @property-read Result $result
 * @property-read mixed  $cell
 * @property-read array  $row
 * @property-read array  $column
 * @property-read array  $rows
 * @property-read array  $assoc
 *
 * @property SelectQuery $distinct  Sets DISTINCT flag to true and returns $this
 * @property SelectQuery $forUpdate Sets FOR UPDATE flag to true and returns $this
 */
class SelectQuery extends Query implements Expressions\Expression
{
    use Expressions\AliasFieldTrait;
    use QueryWhereTrait;

    public const C_COLUMNS = 0x01,
        C_TABLE = 0x02,
        C_WHERE = 0x04,
        C_GROUP_BY = 0x08,
        C_ORDER_BY = 0x10,
        C_HAVING = 0x20,
        C_LIMIT = 0x40,
        C_DISTINCT = 0x80,
        C_FOR_UPDATE = 0x0100,
        C_COMBINING = 0x0200;

    private ?ColumnsClause $columnsClause = null;
    private ?GroupByClause $groupByClause = null;
    private ?OrderByClause $orderByClause = null;
    private ?Condition $havingClause = null;
    private ?LimitClause $limitClause = null;
    private bool $distinctFlag = false;
    private string|bool $forUpdateClause = false;
    private ?CombiningClause $combiningClause = null;

    public function __get($var)
    {
        return match ($var) {
            'result' => $this->result(),
            'cell' => $this->cell(),
            'row' => $this->row(),
            'column' => $this->column(),
            'rows' => $this->rows(),
            'assoc' => $this->assoc(),
            'forUpdate' => $this->forUpdate(),
            'distinct' => $this->distinct(),
            default => throw new \LogicException("Undefined property $var"),
        };
    }

    /**
     * @return bool
     */
    public function isDistinct(): bool
    {
        return $this->distinctFlag;
    }

    /**
     * Sets DISTINCT flag
     *
     * @param bool $flag
     *
     * @return $this
     */
    public function distinct(bool $flag = true): static
    {
        $this->distinctFlag = $flag;

        return $this;
    }

    /**
     * @return bool|string
     */
    public function getForUpdateClause(): bool|string
    {
        return $this->forUpdateClause;
    }

    /**
     * Sets or returns FOR UPDATE flag
     *
     * @param bool|string $flag
     *
     * @return $this
     */
    public function forUpdate(bool|string $flag = true): static
    {
        $this->forUpdateClause = $flag;

        return $this;
    }

    /**
     * Sets column list to sql
     *
     * @param string|array|Expression ...$columns
     *
     * @return $this
     */
    public function columns(string|array|Expression ...$columns): static
    {
        $clause = $this->createColumnsClause()->add(...$columns);

        return $this->setColumnsClause($clause);
    }

    /**
     * Appends column list
     *
     * @param string|array|Expression ...$columns
     *
     * @return $this
     */
    public function addColumns(string|array|Expression ...$columns): static
    {
        $this->getColumnsClause()->add(...$columns);

        return $this;
    }

    /**
     * @param iterable $columns
     *
     * @return $this
     */
    public function addAliasedColumns(iterable $columns): static
    {
        foreach ($columns as $alias => $column) {
            if (is_string($column) && strtolower($column) == strtolower($alias)) {
                $this->addColumns($column);
            } else {
                $this->addColumns($column . ' ' . $alias);
            }
        }

        return $this;
    }

    /**
     * Appends nested columns
     *
     * @param string|string[]       $path
     * @param string|int|Expression ...$columns
     *
     * @return $this
     */
    public function addNestedColumns(array|string $path, string|int|Expression ...$columns): static
    {
        $alias = $this->getTableClause()->getLastTableAlias();
        $this->getColumnsClause()->addNested($path, $alias, ...$columns);

        return $this;
    }


    /**
     * Add from clause in sql
     *
     * @param string|Table|Expression $table
     * @param string|null             $alias
     *
     * @return $this
     */
    public function from(string|Table|Expression $table, string $alias = null): static
    {
        return $this->setMainTable($table, $alias);
    }

    /**
     * INNER JOIN.  See {@link TableClause::join}
     *
     * @param string|Table|Expression     $table
     * @param string|array|Condition|null $on
     * @param string|null                 $alias
     *
     * @return $this
     */
    public function join(
        string|Table|Expression $table,
        string|array|Condition $on = null,
        string $alias = null
    ): static {
        $this->getTableClause()->join($table, $on, $alias);

        return $this;
    }

    /**
     * LEFT JOIN. See {@link TableClause::left}
     *
     * @param string|Table|Expression     $table
     * @param string|array|Condition|null $on
     * @param string|null                 $alias
     *
     * @return $this
     */
    public function left(
        string|Table|Expression $table,
        string|array|Condition $on = null,
        string $alias = null
    ): static {
        $this->getTableClause()->left($table, $on, $alias);

        return $this;
    }

    /**
     * RIGHT JOIN. See {@link TableClause::right}
     *
     * @param string|Table|Expression     $table
     * @param string|array|Condition|null $on
     * @param string|null                 $alias
     *
     * @return $this
     */
    public function right(
        string|Table|Expression $table,
        string|array|Condition $on = null,
        string $alias = null
    ): static {
        $this->getTableClause()->right($table, $on, $alias);

        return $this;
    }

    /**
     * FULL JOIN. {@link TableClause::full}
     *
     * @param string|Table|Expression     $table
     * @param string|array|Condition|null $on
     * @param string|null                 $alias
     *
     * @return $this
     */
    public function full(
        string|Table|Expression $table,
        string|array|Condition $on = null,
        string $alias = null
    ): static {
        $this->getTableClause()->full($table, $on, $alias);

        return $this;
    }

    /**
     * See {@link TableClause::joinBack}
     *
     * @param string|Table|Expression $table
     * @param string|null             $onTable
     * @param string|null             $alias
     *
     * @return $this
     */
    public function joinBack(string|Table|Expression $table, string $onTable = null, string $alias = null): static
    {
        $this->getTableClause()->joinBack($table, $onTable, $alias);

        return $this;
    }

    /**
     * See {@link TableClause::leftBack}
     *
     * @param string|Table|Expression $table
     * @param string|null             $onTable
     * @param string|null             $alias
     *
     * @return $this
     */
    public function leftBack(string|Table|Expression $table, string $onTable = null, string $alias = null): static
    {
        $this->getTableClause()->leftBack($table, $onTable, $alias);

        return $this;
    }

    /**
     * See {@link TableClause::joinParent}
     *
     * @param string      $alias
     * @param string|null $onTable
     * @param string|null $parentColumn Default - "parent_id"
     *
     * @return $this
     */
    public function joinParent(string $alias, string $onTable = null, string $parentColumn = null): static
    {
        $this->getTableClause()->joinParent($alias, $onTable, $parentColumn);

        return $this;
    }

    /**
     * See {@link TableClause::leftParent}
     *
     * @param string      $alias
     * @param string|null $onTable
     * @param string|null $parentColumn Default - "parent_id"
     *
     * @return $this
     */
    public function leftParent(string $alias, string $onTable = null, string $parentColumn = null): static
    {
        $this->getTableClause()->leftParent($alias, $onTable, $parentColumn);

        return $this;
    }

    /**
     * @param Table|SelectQuery           $table
     * @param string|array|Condition|null $on
     * @param array|string|null           $path
     * @param string|null                 $alias
     *
     * @return $this
     */
    public function joinNestedColumns(
        Table|SelectQuery $table,
        string|array|Condition $on = null,
        array|string $path = null,
        string $alias = null,
    ): static {
        return $this->addJoinNestedColumns([$this, 'join'], $table, $on, $path, $alias);
    }

    /**
     * @param Table|SelectQuery           $table
     * @param string|array|Condition|null $on
     * @param array|string|null           $path
     * @param string|null                 $alias
     *
     * @return $this
     */
    public function leftNestedColumns(
        Table|SelectQuery $table,
        string|array|Condition $on = null,
        array|string $path = null,
        string $alias = null,
    ): static {
        return $this->addJoinNestedColumns([$this, 'left'], $table, $on, $path, $alias);
    }

    /**
     * Add from clause in sql
     *
     * @param array|string $index
     *
     * @return $this
     */
    public function useIndex(array|string $index): static
    {
        $this->getTableClause()->useIndex($index);

        return $this;
    }

    /**
     * Add GROUP BY clause to sql
     *
     * @param string[]|Expression[] $columns
     *
     * @return $this
     */
    public function groupBy(...$columns): static
    {
        $clause = $this->createGroupByClause()->add(...$columns);

        return $this->setGroupByClause($clause);
    }

    /**
     * Appends GROUP BY clause
     *
     * @param string[]|Expression[] $columns
     *
     * @return $this
     */
    public function addGroupBy(...$columns): static
    {
        $this->getGroupByClause()->add(...$columns);

        return $this;
    }

    /**
     * Add `HAVING` clause
     *
     * @param string|int|Expression|Predicate|array|null $expression
     * @param mixed|array|Expression|null                $param
     *
     * @return SelectQuery
     */
    public function having(string|int|Expression|Predicate|array|null $expression, mixed $param = null): SelectQuery
    {
        return $this->conditionAnd($this->getHavingClause(), ...func_get_args());
    }

    /**
     * Adds ORDER BY clause to sql
     *
     * @param string[]|Expression[] $columns
     *
     * @return $this
     */
    public function orderBy(...$columns): static
    {
        $clause = $this->createOrderByClause()->add(...$columns);

        return $this->setOrderByClause($clause);
    }

    /**
     * Appends ORDER BY clause
     *
     * @param string[]|Expression[] $columns
     *
     * @return $this
     */
    public function addOrderBy(...$columns): static
    {
        $this->getOrderByClause()->add(...$columns);

        return $this;
    }

    /**
     * Add `LIMIT`
     *
     * @param int $count
     * @param int $offset
     *
     * @return $this
     */
    public function limit(int $count, int $offset = 0): static
    {
        $this->getLimitClause()->set($count, $offset);

        return $this;
    }

    /**
     * Makes this query a combined query with UNION of $queries
     */
    public function union(self ...$queries): static
    {
        $this->getCombiningClause()->add(CombiningClause::CONN_UNION, false, ...$queries);

        return $this;
    }

    /**
     * Makes this query a combined query with UNION ALL of $queries
     */
    public function unionAll(self ...$queries): static
    {
        $this->getCombiningClause()->add(CombiningClause::CONN_UNION, true, ...$queries);

        return $this;
    }

    /**
     * Makes this query a combined query with INTERSECT of $queries
     */
    public function intersect(self ...$queries): static
    {
        $this->getCombiningClause()->add(CombiningClause::CONN_INTERSECT, false, ...$queries);

        return $this;
    }

    /**
     * Makes this query a combined query with INTERSECT ALL of $queries
     */
    public function intersectAll(self ...$queries): static
    {
        $this->getCombiningClause()->add(CombiningClause::CONN_INTERSECT, true, ...$queries);

        return $this;
    }

    /**
     * Makes this query a combined query with EXCEPT of $queries
     */
    public function except(self ...$queries): static
    {
        $this->getCombiningClause()->add(CombiningClause::CONN_EXCEPT, false, ...$queries);

        return $this;
    }

    /**
     * Makes this query a combined query with EXCEPT ALL of $queries
     */
    public function exceptAll(self ...$queries): static
    {
        $this->getCombiningClause()->add(CombiningClause::CONN_EXCEPT, true, ...$queries);

        return $this;
    }

    /**
     * @param int|null             $flags
     * @param string|\Closure|null $decorator
     * @param int|null             $fetchSize
     *
     * @return Result
     */
    public function result(int $flags = null, string|\Closure $decorator = null, int $fetchSize = null): Result
    {
        return $this->getConnectionOrThrow()->query($this, null, $flags, $decorator, $fetchSize);
    }

    /**
     * @param int      $columnIndex
     * @param int|null $flags
     *
     * @return mixed
     */
    public function cell(int $columnIndex = 0, int $flags = null): mixed
    {
        return $this->result()->cell($columnIndex, $flags);
    }

    /**
     * @param int|null $flags
     *
     * @return array|null
     */
    public function row(int $flags = null): ?array
    {
        return $this->result()->row($flags);
    }

    /**
     * Returns first column with $index
     *
     * @param int      $index
     * @param int|null $flags One or more of VV\Db::FETCH_*
     *
     * @return array
     */
    public function column(int $index = 0, int $flags = null): array
    {
        return $this->result()->column($index, $flags);
    }

    /**
     * @param int|null                 $flags
     * @param string|int|null          $keyColumn
     * @param string|int|\Closure|null $decorator
     *
     * @return array[]
     */
    public function rows(int $flags = null, string|int $keyColumn = null, string|int|\Closure $decorator = null): array
    {
        return $this->result()->rows($flags, $keyColumn, $decorator);
    }

    /**
     * @param string|null $keyColumn
     * @param string|null $valueColumn
     *
     * @return array
     */
    public function assoc(string $keyColumn = null, string $valueColumn = null): array
    {
        return $this->result()->assoc($keyColumn, $valueColumn);
    }

    /**
     * Returns columnsClause
     *
     * @return ColumnsClause
     */
    public function getColumnsClause(): ColumnsClause
    {
        if (!$this->columnsClause) {
            $this->setColumnsClause($this->createColumnsClause());
        }

        return $this->columnsClause;
    }

    /**
     * Sets columnsClause
     *
     * @param ColumnsClause|null $columnsClause
     *
     * @return $this
     */
    public function setColumnsClause(?ColumnsClause $columnsClause): static
    {
        $this->columnsClause = $columnsClause?->setTableClause($this->getTableClause());

        return $this;
    }

    /**
     * Creates default columnsClause
     *
     * @return ColumnsClause
     */
    public function createColumnsClause(): ColumnsClause
    {
        return new ColumnsClause();
    }

    /**
     * Returns groupByClause
     *
     * @return GroupByClause
     */
    public function getGroupByClause(): GroupByClause
    {
        if (!$this->groupByClause) {
            $this->setGroupByClause($this->createGroupByClause());
        }

        return $this->groupByClause;
    }

    /**
     * Sets groupByClause
     *
     * @param GroupByClause|null $groupByClause
     *
     * @return $this
     */
    public function setGroupByClause(?GroupByClause $groupByClause): static
    {
        $this->groupByClause = $groupByClause;

        return $this;
    }

    /**
     * Creates default groupByClause
     *
     * @return GroupByClause
     */
    public function createGroupByClause(): GroupByClause
    {
        return new GroupByClause();
    }

    /**
     * Returns havingClause
     *
     * @return Condition
     */
    public function getHavingClause(): Condition
    {
        if (!$this->havingClause) {
            $this->setHavingClause($this->createHavingClause());
        }

        return $this->havingClause;
    }

    /**
     * Sets havingClause
     *
     * @param Condition|null $havingClause
     *
     * @return $this
     */
    public function setHavingClause(?Condition $havingClause): static
    {
        $this->havingClause = $havingClause;

        return $this;
    }

    /**
     * Creates default havingClause
     *
     * @return Condition
     */
    public function createHavingClause(): Condition
    {
        return Sql::condition();
    }

    /**
     * Returns orderByClause
     *
     * @return OrderByClause
     */
    public function getOrderByClause(): OrderByClause
    {
        if (!$this->orderByClause) {
            $this->setOrderByClause($this->createOrderByClause());
        }

        return $this->orderByClause;
    }

    /**
     * Sets orderByClause
     *
     * @param OrderByClause|null $orderByClause
     *
     * @return $this
     */
    public function setOrderByClause(?OrderByClause $orderByClause): static
    {
        $this->orderByClause = $orderByClause;

        return $this;
    }

    /**
     * Creates default orderByClause
     *
     * @return OrderByClause
     */
    public function createOrderByClause(): OrderByClause
    {
        return new OrderByClause();
    }

    /**
     * Returns limitClause
     *
     * @return LimitClause
     */
    public function getLimitClause(): LimitClause
    {
        if (!$this->limitClause) {
            $this->setLimitClause($this->createLimitClause());
        }

        return $this->limitClause;
    }

    /**
     * Sets limitClause
     *
     * @param LimitClause|null $limitClause
     *
     * @return $this
     */
    public function setLimitClause(?LimitClause $limitClause): static
    {
        $this->limitClause = $limitClause;

        return $this;
    }

    /**
     * Creates default limitClause
     *
     * @return LimitClause
     */
    public function createLimitClause(): LimitClause
    {
        return new LimitClause();
    }

    /**
     * Returns combiningClause
     *
     * @return CombiningClause
     */
    public function getCombiningClause(): CombiningClause
    {
        if (!$this->combiningClause) {
            $this->setCombiningClause($this->createCombiningClause());
        }

        return $this->combiningClause;
    }

    /**
     * Sets combiningClause
     *
     * @param CombiningClause|null $combiningClause
     *
     * @return $this
     */
    public function setCombiningClause(?CombiningClause $combiningClause): static
    {
        $this->combiningClause = $combiningClause;

        return $this;
    }

    /**
     * Creates default combiningClause
     *
     * @return CombiningClause
     */
    public function createCombiningClause(): CombiningClause
    {
        return new CombiningClause();
    }

    /**
     * @return array|null
     */
    public function getResultColumnsMap(): ?array
    {
        return $this->getColumnsClause()->getResultColumnsMap();
    }

    /**
     * @inheritDoc
     */
    public function getExpressionId(): string
    {
        return spl_object_hash($this);
    }

    protected function addJoinNestedColumns(
        callable $joinCallback,
        Table|SelectQuery $from,
        string|array|Condition $on = null,
        array|string $path = null,
        string $alias = null
    ): static {
        if (!$path && $on) {
            if (!is_string($on)) {
                throw new \LogicException('$path not set and $on is not string');
            }

            $nameRx = DbObject::NAME_RX;
            if (preg_match("/^$nameRx\.($nameRx)$/", $on, $m)) {
                $path = $m[1];
            }
        }
        if (!$alias) {
            if (is_array($path)) {
                $alias = end($path);
            } else {
                $alias = (string)$path;
            }
        }

        $joinCallback($from, $on, $alias);

        $alias = $this->getTableClause()->getLastTableAlias();
        $this->getColumnsClause()->addNestedFrom($from, $path, $alias);

        return $this;
    }

    protected function getNonEmptyClausesMap(): array
    {
        return [
            self::C_COLUMNS => $this->getColumnsClause(),
            self::C_TABLE => $this->getTableClause(),
            self::C_WHERE => $this->getWhereClause(),
            self::C_GROUP_BY => $this->getGroupByClause(),
            self::C_HAVING => $this->getHavingClause(),
            self::C_ORDER_BY => $this->getOrderByClause(),
            self::C_LIMIT => $this->getLimitClause(),
            self::C_DISTINCT => $this->isDistinct(),
            self::C_FOR_UPDATE => $this->getForUpdateClause(),
            self::C_COMBINING => $this->getCombiningClause(),
        ];
    }

}
