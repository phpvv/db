<?php /** @noinspection PhpUnnecessaryFullyQualifiedNameInspection */
declare(strict_types=1);

/*
 * This file is part of the VV package.
 *
 * (c) Volodymyr Sarnytskyi <v00v4n@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
namespace VV\Db\Sql;

use JetBrains\PhpStorm\Pure;
use VV\Db\Model\Table;
use VV\Db\Sql;
use VV\Db\Sql\Clauses\ColumnsClause;
use VV\Db\Sql\Clauses\GroupByClause;
use VV\Db\Sql\Clauses\LimitClause;
use VV\Db\Sql\Clauses\OrderByClause;
use VV\Db\Sql\Clauses\QueryWhereTrait;
use VV\Db\Sql\Clauses\TableClause;
use VV\Db\Sql\Expressions\Expression;
use VV\Db\Sql\Predicates\Predicate;

/**
 * Class SelectQuery
 *
 * @package VV\Db\Sql
 *
 * @property-read \VV\Db\Result $result
 * @property-read mixed         $column
 * @property-read array         $row
 * @property-read array         $rows
 * @property-read array         $assoc
 *
 * @property SelectQuery        $distinct  Sets DISTINCT flag to true and returns $this
 * @property SelectQuery        $noCahce   Sets NO CACHE flag to true and returns $this
 * @property SelectQuery        $forUpdate Sets FOR UPDATE flag to true and returns $this
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
        C_NOCACHE = 0x0100,
        C_FOR_UPDATE = 0x0200,
        C_HINT = 0x0400;

    protected const MAX_RESULT_FIELD_NAME_LEN = 30;

    private ?ColumnsClause $columnsClause = null;
    private ?GroupByClause $groupByClause = null;
    private ?OrderByClause $orderByClause = null;
    private ?Condition $havingClause = null;
    private ?LimitClause $limitClause = null;
    private bool $distinctFlag = false;
    private bool $noCacheFlag = false;
    private string|bool $forUpdateClause = false;

    public function __get($var)
    {
        switch ($var) {
            // call method wo params
            case 'result':
                return $this->result();
            case 'row':
                return $this->row();
            case 'rows':
                return $this->rows();
            case 'assoc':
                return $this->assoc();
            case 'column':
                return $this->column();

            case 'noCache':
                return $this->noCache();
            case 'forUpdate':
                return $this->forUpdate();
            case 'distinct':
                return $this->distinct();
        }

        throw new \LogicException("Undefined property $var");
    }

    /**
     * @return boolean
     */
    public function isNoCache(): bool
    {
        return $this->noCacheFlag;
    }

    /**
     * Sets NO CACHE flag
     *
     * @param bool $flag
     *
     * @return $this
     */
    public function noCache(bool $flag = true): static
    {
        $this->noCacheFlag = $flag;

        return $this;
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
    public function forUpdateClause(): bool|string
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
    public function columns(...$columns): static
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
    public function addColumns(...$columns): static
    {
        $this->columnsClause()->add(...$columns);

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
     * Appends columns list
     *
     * @param string|string[]       $group
     * @param string                $dfltTableAlias
     * @param string|int|Expression ...$columns
     *
     * @return $this
     */
    public function addColumnsGroup(
        array|string $group,
        string $dfltTableAlias,
        string|int|Expression ...$columns
    ): static {
        if (!is_array($group)) {
            $group = [$group];
        }

        // todo: need review/refactoring
        $columnsClause = $this->columnsClause();
        $map = $columnsClause->resultFieldsMap();
        foreach ($columns as &$col) {
            if (is_string($col)) {
                $col = Expressions\DbObject::create($col, $dfltTableAlias);
                $col->as($col->resultName());
            }

            if (!$col instanceof Expressions\Expression) {
                throw new \InvalidArgumentException('$column must be string or Sql\Expr');
            }

            $path = $group;
            $path[] = $col->alias();

            // build short alias name
            $sqlAlias = $this->buildColumnsGroupAlias($path, $map);

            // add path to map and save column alias
            $map[$sqlAlias] = $path;
            $col->as($sqlAlias);
        }
        unset($col);

        $columnsClause->setResultFieldsMap($map);

        return $this->addColumns(...$columns);
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
        return $this->table($table, $alias);
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
        $this->tableClause()->join($table, $on, $alias);

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
        $this->tableClause()->left($table, $on, $alias);

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
        $this->tableClause()->right($table, $on, $alias);

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
        $this->tableClause()->full($table, $on, $alias);

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
        $this->tableClause()->joinBack($table, $onTable, $alias);

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
        $this->tableClause()->leftBack($table, $onTable, $alias);

        return $this;
    }

    /**
     * See {@link TableClause::joinParent}
     *
     * @param string      $alias
     * @param string|null $onTable
     * @param string|null $parentField Default - "parent_id"
     *
     * @return $this
     */
    public function joinParent(string $alias, string $onTable = null, string $parentField = null): static
    {
        $this->tableClause()->joinParent($alias, $onTable, $parentField);

        return $this;
    }

    /**
     * See {@link TableClause::leftParent}
     *
     * @param string      $alias
     * @param string|null $onTable
     * @param string|null $parentField Default - "parent_id"
     *
     * @return $this
     */
    public function leftParent(string $alias, string $onTable = null, string $parentField = null): static
    {
        $this->tableClause()->leftParent($alias, $onTable, $parentField);

        return $this;
    }

    /**
     * @param Table|SelectQuery           $table
     * @param string|array|Condition|null $on
     * @param array|string|null           $group
     * @param string|null                 $alias
     *
     * @return $this
     */
    public function joinAsColumnsGroup(
        Table|SelectQuery $table,
        string|array|Condition $on = null,
        array|string $group = null,
        string $alias = null,
    ): static {
        return $this->_joinAsColumnsGroup([$this, 'join'], $table, $on, $group, $alias);
    }

    /**
     * @param Table|SelectQuery           $table
     * @param string|array|Condition|null $on
     * @param array|string|null           $group
     * @param string|null                 $alias
     *
     * @return $this
     */
    public function leftAsColumnsGroup(
        Table|SelectQuery $table,
        string|array|Condition $on = null,
        array|string $group = null,
        string $alias = null,
    ): static {
        return $this->_joinAsColumnsGroup([$this, 'left'], $table, $on, $group, $alias);
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
        $this->orderByClause()->add(...$columns);

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
        $this->groupByClause()->add(...$columns);

        return $this;
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
        $this->tableClause()->useIndex($index);

        return $this;
    }

    /**
     * Add `HAVING` clause
     *
     * @param string|int|array|Expression|Predicate|null $field
     * @param mixed                                      $value
     *
     * @return SelectQuery
     */
    public function having(string|int|array|Expression|Predicate|null $field, mixed $value = null): SelectQuery
    {
        return $this->condintionAnd($this->havingClause(), ...func_get_args());
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
        $this->limitClause()->set($count, $offset);

        return $this;
    }

    /**
     * @param null $flags
     * @param null $decorator
     * @param null $fetchSize
     *
     * @return \VV\Db\Result
     */
    public function result($flags = null, $decorator = null, $fetchSize = null): \VV\Db\Result
    {
        return $this->_result($flags, $decorator, $fetchSize);
    }

    /**
     * @param int      $index
     * @param int|null $flags
     *
     * @return mixed
     */
    public function column(int $index = 0, int $flags = null): mixed
    {
        return $this->_result()->column($index, $flags);
    }

    /**
     * @param int|null $flags
     *
     * @return array|null
     */
    public function row(int $flags = null): ?array
    {
        return $this->_result()->row($flags);
    }

    /**
     * @param int|null             $flags
     * @param string|null          $keyColumn
     * @param string|\Closure|null $decorator
     *
     * @return array[]
     */
    public function rows(int $flags = null, string $keyColumn = null, string|\Closure $decorator = null): array
    {
        return $this->_result()->rows($flags, $keyColumn, $decorator);
    }

    /**
     * @param string|null $keyColumn
     * @param string|null $valueColumn
     *
     * @return array
     */
    public function assoc(string $keyColumn = null, string $valueColumn = null): array
    {
        return $this->_result()->assoc($keyColumn, $valueColumn);
    }

    /**
     * Returns columnsClause
     *
     * @return ColumnsClause
     */
    public function columnsClause(): ColumnsClause
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
        $this->columnsClause = $columnsClause->setTableClause($this->tableClause());

        return $this;
    }

    /**
     * Clears columnsClause property and returns previous value
     *
     * @return ColumnsClause
     */
    public function clearColumnsClause(): ColumnsClause
    {
        try {
            return $this->columnsClause();
        } finally {
            $this->setColumnsClause(null);
        }
    }

    /**
     * Creates default columnsClause
     *
     * @return ColumnsClause
     */
    #[Pure]
    public function createColumnsClause(): ColumnsClause
    {
        return new ColumnsClause();
    }

    /**
     * Returns groupByClause
     *
     * @return GroupByClause
     */
    public function groupByClause(): GroupByClause
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
     * Clears groupByClause property and returns previous value
     *
     * @return GroupByClause
     */
    public function clearGroupByClause(): GroupByClause
    {
        try {
            return $this->groupByClause();
        } finally {
            $this->setGroupByClause(null);
        }
    }

    /**
     * Creates default groupByClause
     *
     * @return GroupByClause
     */
    #[Pure]
    public function createGroupByClause(): GroupByClause
    {
        return new GroupByClause();
    }

    /**
     * Returns havingClause
     *
     * @return Condition
     */
    public function havingClause(): Condition
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
     * Clears havingClause property and returns previous value
     *
     * @return Condition
     */
    public function clearHavingClause(): Condition
    {
        try {
            return $this->havingClause();
        } finally {
            $this->setHavingClause(null);
        }
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
    public function orderByClause(): OrderByClause
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
     * Clears orderByClause property and returns previous value
     *
     * @return OrderByClause
     */
    public function clearOrderByClause(): OrderByClause
    {
        try {
            return $this->orderByClause();
        } finally {
            $this->setOrderByClause(null);
        }
    }

    /**
     * Creates default orderByClause
     *
     * @return OrderByClause
     */
    #[Pure]
    public function createOrderByClause(): OrderByClause
    {
        return new OrderByClause();
    }

    /**
     * Returns limitClause
     *
     * @return LimitClause
     */
    public function limitClause(): LimitClause
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
    public function setLimitClause(LimitClause $limitClause = null): static
    {
        $this->limitClause = $limitClause;

        return $this;
    }

    /**
     * Clears limitClause property and returns previous value
     *
     * @return LimitClause
     */
    public function clearLimitClause(): LimitClause
    {
        try {
            return $this->limitClause();
        } finally {
            $this->setLimitClause(null);
        }
    }

    /**
     * Creates default limitClause
     *
     * @return LimitClause
     */
    #[Pure]
    public function createLimitClause(): LimitClause
    {
        return new LimitClause();
    }

    /**
     * @return array|null
     */
    public function resultFieldsMap(): ?array
    {
        return $this->columnsClause()->resultFieldsMap();
    }

    /**
     * @inheritDoc
     */
    public function expressionId(): string
    {
        return spl_object_hash($this);
    }

    protected function _joinAsColumnsGroup(
        callable $joinCallback,
        Table|SelectQuery $from,
        string|array|Condition $on = null,
        array|string $group = null,
        string $alias = null
    ): static {
        if (!$group && $on) {
            if (!is_string($on)) {
                throw new \LogicException('$group not set and $on is not string');
            }

            $namerx = Expressions\DbObject::NAME_RX;
            if (preg_match("/^$namerx\.($namerx)$", $on, $m)) {
                $group = $m[1];
            }
        }
        if (!$alias) {
            $alias = $group;
        }

        $joinCallback($from, $on, $alias);
        $alias = $this->tableClause()->lastTableAlias();

        if (!is_array($group)) {
            $group = [$group];
        }

        if ($from instanceof Table) {
            return $this->addColumnsGroup($group, $alias, ...$from->getFields()->getNames());
        }

        if ($from instanceof SelectQuery) {
            $resultFields = $from->columnsClause()->resultFields();
            // todo: need review/refactoring
            if ($joinMap = $from->resultFieldsMap()) {
                $resultFields = array_diff($resultFields, array_keys($joinMap));
                $columnsClause = $this->columnsClause();

                $map = $columnsClause->resultFieldsMap();
                foreach ($joinMap as $subField => $path) {
                    $jPath = array_merge($group, $path);
                    $sqlAlias = $this->buildColumnsGroupAlias($jPath, $map);
                    $map[$sqlAlias] = $jPath;
                    $this->addColumns("$alias.$subField $sqlAlias");
                }

                $columnsClause->setResultFieldsMap($map);
            }

            return $this->addColumnsGroup($group, $alias, ...$resultFields);
        }

        throw new \InvalidArgumentException('Wrong $table type');
    }

    protected function nonEmptyClausesMap(): array
    {
        return [
            self::C_COLUMNS => $this->columnsClause(),
            self::C_TABLE => $this->tableClause(),
            self::C_WHERE => $this->getWhereClause(),
            self::C_GROUP_BY => $this->groupByClause(),
            self::C_HAVING => $this->havingClause(),
            self::C_ORDER_BY => $this->orderByClause(),
            self::C_LIMIT => $this->limitClause(),
            self::C_DISTINCT => $this->isDistinct(),
            self::C_NOCACHE => $this->isNoCache(),
            self::C_FOR_UPDATE => $this->forUpdateClause(),
            self::C_HINT => $this->hintClause(),
        ];
    }

    /**
     * @param array      $path
     * @param array|null $resultFieldsMap
     *
     * @return string
     */
    public static function buildColumnsGroupAlias(array $path, ?array $resultFieldsMap): string
    {
        $sqlAlias = '$' . implode('_', $path);
        $maxlen = static::MAX_RESULT_FIELD_NAME_LEN;
        $len = strlen($sqlAlias);
        if ($len > $maxlen) {
            $sqlAlias = substr($sqlAlias, 0, $maxlen);
            $len = $maxlen;
        }

        $i = 1;
        if ($resultFieldsMap) {
            $d = $maxlen - $len;

            while (array_key_exists($sqlAlias, $resultFieldsMap)) {
                $sfx = '_' . $i++;
                $sfxLen = strlen($sfx);

                $cutAlias = $d < $sfxLen
                    ? substr($sqlAlias, 0, $maxlen - $sfxLen + $d)
                    : $sqlAlias;

                $sqlAlias = $cutAlias . $sfx;
            }
        }

        return $sqlAlias;
    }
}
