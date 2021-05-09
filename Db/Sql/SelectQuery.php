<?php declare(strict_types=1);
/** @noinspection PhpUnnecessaryFullyQualifiedNameInspection */

/*
 * This file is part of the VV package.
 *
 * (c) Volodymyr Sarnytskyi <v00v4n@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
namespace VV\Db\Sql;

use VV\Db\Sql;

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
class SelectQuery extends \VV\Db\Sql\Query implements Expressions\Expression {

    use Expressions\AliasFieldTrait;

    const C_COLUMNS = 0x01,
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

    const MAX_RESULT_FIELD_NAME_LEN = 30;

    private ?Clauses\ColumnsClause $columnsClause = null;

    private ?Clauses\GroupByClause $groupByClause = null;

    private ?Clauses\OrderByClause $orderByClause = null;

    private ?Condition\Condition $havingClause = null;

    private ?Clauses\LimitClause $limitClause = null;

    private bool $distinctFlag = false;

    private bool $noCacheFlag = false;

    /** @var bool|string */
    private $forUpdateClause = false;

    public function __get($var) {
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
    public function isNoCache() {
        return $this->noCacheFlag;
    }

    /**
     * Sets NO CACHE flag
     *
     * @param bool $flag
     *
     * @return $this|bool
     */
    public function noCache($flag = true) {
        $this->noCacheFlag = (bool)$flag;

        return $this;
    }

    /**
     * @return boolean
     */
    public function isDistinct() {
        return $this->distinctFlag;
    }

    /**
     * Sets DISTINCT flag
     *
     * @param bool $flag
     *
     * @return $this
     */
    public function distinct($flag = true) {
        $this->distinctFlag = (bool)$flag;

        return $this;
    }

    /**
     * @return boolean|string
     */
    public function forUpdateClause() {
        return $this->forUpdateClause;
    }

    /**
     * Sets or returns FOR UPDATE flag
     *
     * @param bool|string $flag
     *
     * @return $this
     */
    public function forUpdate($flag = true) {
        $this->forUpdateClause = $flag;

        return $this;
    }

    /**
     * Sets column list to sql
     *
     * @param string[]|array|\VV\Db\Sql\Expressions\Expression[] $columns
     *
     * @return $this
     */
    public function columns(...$columns) {
        $clause = $this->createColumnsClause()->add(...$columns);

        return $this->setColumnsClause($clause);
    }

    /**
     * Appends column list
     *
     * @param string[]|\VV\Db\Sql\Expressions\Expression[] $columns
     *
     * @return $this
     */
    public function addColumns(...$columns) {
        $this->columnsClause()->add(...$columns);

        return $this;
    }

    public function addAliasedColumns($columns) {
        if (!is_iterable($columns)) {
            throw new \InvalidArgumentException('Columns must be an array or \Traversable');
        }

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
     * @param string|string[]                              $group
     * @param string                                       $dfltTableAlias
     * @param string[]|\VV\Db\Sql\Expressions\Expression[] $columns
     *
     * @return $this
     */
    public function addColumnsGroup($group, string $dfltTableAlias, ...$columns) {
        if (!is_array($group)) $group = [$group];

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
     * @param string|\VV\Db\Model\Table $tbl
     * @param string|null               $alias
     *
     * @return $this
     */
    public function from($tbl, $alias = null) {
        return $this->table($tbl, $alias);
    }

    /**
     * See {@link \VV\Db\Sql\Clauses\TableClause::join}
     *
     * @param \VV\Db\Model\Table|string $tbl
     * @param string|null               $on
     * @param string|null               $alias
     *
     * @return $this
     */
    public function join($tbl, $on = null, $alias = null) {
        $this->tableClause()->join($tbl, $on, $alias);

        return $this;
    }

    /**
     * See {@link \VV\Db\Sql\Clauses\TableClause::left}
     *
     * @param \VV\Db\Model\Table|string $tbl
     * @param string|null               $on
     * @param string|null               $alias
     *
     * @return $this
     */
    public function left($tbl, $on = null, $alias = null) {
        $this->tableClause()->left($tbl, $on, $alias);

        return $this;
    }

    /**
     * See {@link \VV\Db\Sql\Clauses\TableClause::right}
     *
     * @param \VV\Db\Model\Table|string $tbl
     * @param string|null               $on
     * @param string|null               $alias
     *
     * @return $this
     */
    public function right($tbl, $on = null, $alias = null) {
        $this->tableClause()->right($tbl, $on, $alias);

        return $this;
    }

    /**
     * See {@link \VV\Db\Sql\Clauses\TableClause::full}
     *
     * @param \VV\Db\Model\Table|string $tbl
     * @param string|null                    $on
     * @param string|null                    $alias
     *
     * @return $this
     */
    public function full($tbl, $on = null, $alias = null) {
        $this->tableClause()->full($tbl, $on, $alias);

        return $this;
    }

    /**
     * See {@link \VV\Db\Sql\Clauses\TableClause::joinBack}
     *
     * @param \VV\Db\Model\Table|string $tbl
     * @param string|null                    $ontbl
     * @param string|null                    $alias
     *
     * @return $this
     */
    public function joinBack($tbl, $ontbl = null, $alias = null) {
        $this->tableClause()->joinBack($tbl, $ontbl, $alias);

        return $this;
    }

    /**
     * See {@link \VV\Db\Sql\Clauses\TableClause::leftBack}
     *
     * @param \VV\Db\Model\Table|string $tbl
     * @param string|null                    $ontbl
     * @param string|null                    $alias
     *
     * @return $this
     */
    public function leftBack($tbl, $ontbl = null, $alias = null) {
        $this->tableClause()->leftBack($tbl, $ontbl, $alias);

        return $this;
    }

    /**
     * See {@link \VV\Db\Sql\Clauses\TableClause::joinParent}
     *
     * @param string      $alias
     * @param string|null $ontbl
     * @param string|null $parentField Default - "parent_id"
     *
     * @return $this
     */
    public function joinParent(string $alias, $ontbl = null, $parentField = null) {
        $this->tableClause()->joinParent($alias, $ontbl, $parentField);

        return $this;
    }

    /**
     * See {@link \VV\Db\Sql\Clauses\TableClause::leftParent}
     *
     * @param string      $alias
     * @param string|null $ontbl
     * @param string|null $parentField Default - "parent_id"
     *
     * @return $this
     */
    public function leftParent(string $alias, $ontbl = null, $parentField = null) {
        $this->tableClause()->leftParent($alias, $ontbl, $parentField);

        return $this;
    }

    /**
     * @param \VV\Db\Model\Table|SelectQuery|string $tbl
     * @param string|\VV\Db\Sql\Condition\Condition $on
     * @param string|null                           $group
     * @param string|null                           $alias
     *
     * @return $this
     */
    public function joinAsColumnsGroup($tbl, $on, string $group = null, string $alias = null) {
        return $this->_joinAsColumnsGroup([$this, 'join'], $tbl, $on, $group, $alias);
    }

    /**
     * @param \VV\Db\Model\Table|SelectQuery|string $tbl
     * @param string|\VV\Db\Sql\Condition\Condition $on
     * @param string|null                           $group
     * @param string|null                           $alias
     *
     * @return $this
     */
    public function leftAsColumnsGroup($tbl, $on, string $group = null, string $alias = null) {
        return $this->_joinAsColumnsGroup([$this, 'left'], $tbl, $on, $group, $alias);
    }

    /**
     * Adds ORDER BY clause to sql
     *
     * @param string[]|\VV\Db\Sql\Expressions\Expression[] $columns
     *
     * @return $this
     */
    public function orderBy(...$columns) {
        $clause = $this->createOrderByClause()->add(...$columns);

        return $this->setOrderByClause($clause);
    }

    /**
     * Appends ORDER BY clause
     *
     * @param string[]|\VV\Db\Sql\Expressions\Expression[] $columns
     *
     * @return $this
     */
    public function addOrderBy(...$columns) {
        $this->orderByClause()->add(...$columns);

        return $this;
    }

    /**
     * Add GROUP BY clause to sql
     *
     * @param string[]|\VV\Db\Sql\Expressions\Expression[] $columns
     *
     * @return $this
     */
    public function groupBy(...$columns) {
        $clause = $this->createGroupByClause()->add(...$columns);

        return $this->setGroupByClause($clause);
    }

    /**
     * Appends GROUP BY clause
     *
     * @param string[]|\VV\Db\Sql\Expressions\Expression[] $columns
     *
     * @return $this
     */
    public function addGroupBy(...$columns) {
        $this->groupByClause()->add(...$columns);

        return $this;
    }

    /**
     * Add from clause in sql
     *
     * @param string|array $index
     *
     * @return $this
     */
    public function useIndex($index) {
        $this->tableClause()->useIndex($index);

        return $this;
    }

    /**
     * Add `HAVING` clause
     *
     * @param \VV\Db\Sql\Expressions\Expression|string      $field
     * @param \VV\Db\Sql\Expressions\Expression|array|mixed $value
     *
     * @return SelectQuery
     */
    public function having($field, $value = null) {
        return $this->condAnd($this->havingClause(), ...func_get_args());
    }

    /**
     * Add `LIMIT`
     *
     * @param int $count
     * @param int $offset
     *
     * @return $this
     */
    public function limit(int $count, $offset = 0) {
        $this->limitClause()->set($count, $offset);

        return $this;
    }

    /**
     * @param null $flags
     * @param null $decorator
     *
     * @return \VV\Db\Result
     * @deprecated Use result method
     */
    public function query($flags = null, $decorator = null) {
        return $this->_result($flags, $decorator);
    }

    /**
     * @param null $flags
     * @param null $decorator
     * @param null $fetchSize
     *
     * @return \VV\Db\Result
     */
    public function result($flags = null, $decorator = null, $fetchSize = null) {
        return $this->_result($flags, $decorator, $fetchSize);
    }

    /**
     * @param int      $index
     * @param int|null $flags
     *
     * @return mixed|null
     */
    public function column(int $index = 0, $flags = null) {
        return $this->_result()->column($index, $flags);
    }

    /**
     * @param int|null $flags
     *
     * @return array|null
     */
    public function row($flags = null) {
        return $this->_result()->row($flags);
    }

    /**
     * @param int|null             $flags
     * @param string|null          $keyColumn
     * @param string|\Closure|null $decorator
     *
     * @return array[]
     */
    public function rows(int $flags = null, string $keyColumn = null, $decorator = null) {
        return $this->_result()->rows($flags, $keyColumn, $decorator);
    }

    /**
     * @param string|null $keyColumn
     * @param string|null $valueColumn
     *
     * @return array
     */
    public function assoc($keyColumn = null, $valueColumn = null) {
        return $this->_result()->assoc($keyColumn, $valueColumn);
    }

    /**
     * Returns columnsClause
     *
     * @return Clauses\ColumnsClause
     */
    public function columnsClause() {
        if (!$this->columnsClause) {
            $this->setColumnsClause($this->createColumnsClause());
        }

        return $this->columnsClause;
    }

    /**
     * Sets columnsClause
     *
     * @param Clauses\ColumnsClause|null $columnsClause
     *
     * @return $this
     */
    public function setColumnsClause(Clauses\ColumnsClause $columnsClause = null) {
        $this->columnsClause = $columnsClause->setTableClause($this->tableClause());

        return $this;
    }

    /**
     * Clears columnsClause property and returns previous value
     *
     * @return Clauses\ColumnsClause
     */
    public function clearColumnsClause() {
        try {
            return $this->columnsClause();
        } finally {
            $this->setColumnsClause(null);
        }
    }

    /**
     * Creates default columnsClause
     *
     * @return Clauses\ColumnsClause
     */
    public function createColumnsClause() {
        return new Clauses\ColumnsClause;
    }

    /**
     * Returns groupByClause
     *
     * @return Clauses\GroupByClause
     */
    public function groupByClause() {
        if (!$this->groupByClause) {
            $this->setGroupByClause($this->createGroupByClause());
        }

        return $this->groupByClause;
    }

    /**
     * Sets groupByClause
     *
     * @param Clauses\GroupByClause|null $groupByClause
     *
     * @return $this
     */
    public function setGroupByClause(Clauses\GroupByClause $groupByClause = null) {
        $this->groupByClause = $groupByClause;

        return $this;
    }

    /**
     * Clears groupByClause property and returns previous value
     *
     * @return Clauses\GroupByClause
     */
    public function clearGroupByClause() {
        try {
            return $this->groupByClause();
        } finally {
            $this->setGroupByClause(null);
        }
    }

    /**
     * Creates default groupByClause
     *
     * @return Clauses\GroupByClause
     */
    public function createGroupByClause() {
        return new Clauses\GroupByClause;
    }

    /**
     * Returns havingClause
     *
     * @return \VV\Db\Sql\Condition\Condition
     */
    public function havingClause() {
        if (!$this->havingClause) {
            $this->setHavingClause($this->createHavingClause());
        }

        return $this->havingClause;
    }

    /**
     * Sets havingClause
     *
     * @param \VV\Db\Sql\Condition\Condition|null $havingClause
     *
     * @return $this
     */
    public function setHavingClause(Condition\Condition $havingClause = null) {
        $this->havingClause = $havingClause;

        return $this;
    }

    /**
     * Clears havingClause property and returns previous value
     *
     * @return \VV\Db\Sql\Condition\Condition
     */
    public function clearHavingClause() {
        try {
            return $this->havingClause();
        } finally {
            $this->setHavingClause(null);
        }
    }

    /**
     * Creates default havingClause
     *
     * @return \VV\Db\Sql\Condition\Condition
     */
    public function createHavingClause() {
        return Sql::condition();
    }

    /**
     * Returns orderByClause
     *
     * @return Clauses\OrderByClause
     */
    public function orderByClause() {
        if (!$this->orderByClause) {
            $this->setOrderByClause($this->createOrderByClause());
        }

        return $this->orderByClause;
    }

    /**
     * Sets orderByClause
     *
     * @param Clauses\OrderByClause|null $orderByClause
     *
     * @return $this
     */
    public function setOrderByClause(Clauses\OrderByClause $orderByClause = null) {
        $this->orderByClause = $orderByClause;

        return $this;
    }

    /**
     * Clears orderByClause property and returns previous value
     *
     * @return Clauses\OrderByClause
     */
    public function clearOrderByClause() {
        try {
            return $this->orderByClause();
        } finally {
            $this->setOrderByClause(null);
        }
    }

    /**
     * Creates default orderByClause
     *
     * @return Clauses\OrderByClause
     */
    public function createOrderByClause() {
        return new Clauses\OrderByClause;
    }

    /**
     * Returns limitClause
     *
     * @return Clauses\LimitClause
     */
    public function limitClause() {
        if (!$this->limitClause) {
            $this->setLimitClause($this->createLimitClause());
        }

        return $this->limitClause;
    }

    /**
     * Sets limitClause
     *
     * @param Clauses\LimitClause|null $limitClause
     *
     * @return $this
     */
    public function setLimitClause(Clauses\LimitClause $limitClause = null) {
        $this->limitClause = $limitClause;

        return $this;
    }

    /**
     * Clears limitClause property and returns previous value
     *
     * @return Clauses\LimitClause
     */
    public function clearLimitClause() {
        try {
            return $this->limitClause();
        } finally {
            $this->setLimitClause(null);
        }
    }

    /**
     * Creates default limitClause
     *
     * @return Clauses\LimitClause
     */
    public function createLimitClause() {
        return new Clauses\LimitClause;
    }

    /**
     * @return array|null
     */
    public function resultFieldsMap(): ?array {
        return $this->columnsClause()->resultFieldsMap();
    }

    public function expressionId(): string {
        return spl_object_hash($this);
    }

    /**
     * @param callable $joinClbc
     * @param          $from
     * @param null     $on
     * @param null     $group
     * @param null     $alias
     *
     * @return $this
     */
    protected function _joinAsColumnsGroup(callable $joinClbc, $from, $on = null, $group = null, $alias = null) {
        if (!$group && $on) {
            $namerx = Expressions\DbObject::NAME_RX;
            if (preg_match("/^$namerx\.($namerx)$", $on, $m)) {
                $group = $m[1];
            }
        }
        if (!$alias) $alias = $group;

        $joinClbc($from, $on, $alias);
        $alias = $this->tableClause()->lastTableAlias();

        if (!is_array($group)) $group = [$group];

        if ($from instanceof \VV\Db\Model\Table) {
            $this->addColumnsGroup($group, $alias, ...$from->fields()->names());
        } elseif ($from instanceof SelectQuery) {
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
            $this->addColumnsGroup($group, $alias, ...$resultFields);
        } else {
            throw new \InvalidArgumentException('Wrong $tbl type');
        }

        return $this;
    }

    protected function nonEmptyClausesMap(): array {
        return [
            self::C_COLUMNS => $this->columnsClause(),
            self::C_TABLE => $this->tableClause(),
            self::C_WHERE => $this->whereClause(),
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
    public static function buildColumnsGroupAlias(array $path, ?array $resultFieldsMap) {
        $sqlAlias = '$' . implode('_', $path);
        $maxlen = self::MAX_RESULT_FIELD_NAME_LEN;
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
