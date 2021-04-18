<?php declare(strict_types=1);

/*
 * This file is part of the VV package.
 *
 * (c) Volodymyr Sarnytskyi <v00v4n@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
namespace VV\Db\Sql;

use VV\Db\Connection;

/**
 * Class Query
 *
 * @package VV\Db\Sql
 */
abstract class Query {

    private ?Connection $connection = null;

    private ?Clauses\Table $tableClause = null;

    private ?\VV\Db\Sql\Condition $whereClause = null;

    private ?string $hintClause = null;

    public function __construct(Connection $connection = null) {
        $this->setConnection($connection);
    }

    /**
     * @return Connection
     */
    public function connection(): ?Connection {
        return $this->connection;
    }

    /**
     * @param Connection|null $connection
     *
     * @return $this
     */
    public function setConnection(?Connection $connection): self {
        $this->connection = $connection;

        return $this;
    }

    /**
     * Add from clause in sql
     *
     * @param string|\VV\Db\Model\Table|null $table
     * @param string|null                    $alias
     *
     * @return $this
     */
    public function table($table = null, string $alias = null) {
        if (!$table) return $this;
        if ($table instanceof Clauses\Table) {
            $this->setTableClause($table);
        } else {
            $this->tableClause()->main($table, $alias);
        }

        return $this;
    }

    /**
     * Add table alias
     *
     * @param string $alias
     *
     * @return $this
     */
    public function a(string $alias) {
        $this->tableClause()->a($alias);

        return $this;
    }

    /**
     * @return string
     */
    public function mainTablePk(): string {
        $table = $this->tableClause();
        if ($table->isEmpty()) throw new \LogicException('Table not selected');
        if (!$pk = $table->mainTablePk()) throw new \LogicException('Can\'t determine PK field');

        return $pk;
    }

    public function mainTableAlias(): string {
        $alias = $this->tableClause()->mainTableAlias();
        if (!$alias) throw new \LogicException('Can\'t determine main table alias');

        return $alias;
    }

    public function lastTableAlias(): string {
        $alias = $this->tableClause()->lastTableAlias();
        if (!$alias) throw new \LogicException('Can\'t determine last table alias');

        return $alias;
    }

    /**
     * Add `WHERE` clause
     *
     * @param Expression|Condition|array|string $field
     * @param Expression|mixed                  $value
     *
     * @return $this
     */
    public function where($field, $value = null) {
        return $this->condAnd($this->whereClause(), ...func_get_args());
    }

    /**
     * Add `WHERE pk_field=`
     *
     *
     * @param Expression|string|int $id
     *
     * @return $this
     */
    public function whereId($id) {
        $this->where($this->mainTablePk(), $id);

        return $this;
    }

    /**
     * @param Expression|string $field
     * @param Expression|mixed  ...$values
     *
     * @return $this
     */
    public function whereIn($field, ...$values) {
        $this->whereClause()->and($field)->in(...$values);

        return $this;
    }

    /**
     * @param Expression|string $field
     * @param Expression|mixed  ...$values
     *
     * @return $this
     */
    public function whereNotIn($field, ...$values) {
        $this->whereClause()->and($field)->not->in(...$values);

        return $this;
    }

    /**
     * @param Expression|mixed ...$values
     *
     * @return $this
     */
    public function whereIdIn(...$values) {
        return $this->whereIn($this->mainTablePk(), ...$values);
    }

    /**
     * @param Expression|mixed ...$values
     *
     * @return $this
     */
    public function whereIdNotIn(...$values) {
        return $this->whereNotIn($this->mainTablePk(), ...$values);
    }

    /**
     * @param \Closure $clbk
     *
     * @return $this
     */
    public function apply(\Closure $clbk) {
        $clbk($this);

        return $this;
    }

    /**
     * @return int
     */
    public function nonEmptyClausesIds() {
        $map = $this->nonEmptyClausesMap();

        return $this->makeNonEmptyClausesBitMask($map);
    }

    /**
     * @return Clauses\Table
     */
    public function tableClause() {
        if (!$this->tableClause) {
            $this->setTableClause($this->createTableClause());
        }

        return $this->tableClause;
    }

    /**
     * @param Clauses\Table $tableClause
     *
     * @return $this
     */
    public function setTableClause(Clauses\Table $tableClause) {
        $this->tableClause = $tableClause;

        return $this;
    }

    /**
     * Clears tableClause property and returns previous value
     *
     * @return Clauses\Table
     */
    public function clearTableClause() {
        try {
            return $this->tableClause();
        } finally {
            $this->setTableClause(null);
        }
    }

    /**
     * @return Clauses\Table
     */
    public function createTableClause() {
        return new Clauses\Table;
    }

    /**
     * @return \VV\Db\Sql\Condition
     */
    public function whereClause() {
        if (!$this->whereClause) {
            $this->setWhereClause($this->createWhereClause());
        }

        return $this->whereClause;
    }

    /**
     * @param \VV\Db\Sql\Condition|null $whereClause
     *
     * @return $this
     */
    public function setWhereClause(\VV\Db\Sql\Condition $whereClause = null) {
        $this->whereClause = $whereClause;

        return $this;
    }

    /**
     * Clears whereClause property and returns previous value
     *
     * @return \VV\Db\Sql\Condition
     */
    public function clearWhereClause() {
        try {
            return $this->whereClause();
        } finally {
            $this->setWhereClause(null);
        }
    }

    /**
     * @return \VV\Db\Sql\Condition
     */
    public function createWhereClause() {
        return \VV\Db\Sql::condition();
    }


    /**
     * @return string
     * todo: reconsider hints uasge
     */
    public function hintClause() {
        return $this->hintClause;
    }

    /**
     * @param string|null $hintClause
     *
     * @return $this
     * todo: reconsider hints uasge
     */
    public function setHintClause(?string $hintClause) {
        $this->hintClause = $hintClause;

        return $this;
    }

    /**
     * @param $hint
     *
     * @return $this
     * todo: reconsider hints uasge
     */
    public function hint($hint) {
        return $this->setHintClause($hint);
    }

    public function toString(&$params = null) {
        return $this->connectionOrThrow()->stringifyQuery($this, $params);
    }

    /**
     * @return \VV\Db\Statement
     */
    public function prepare() {
        return $this->connectionOrThrow()->prepare($this);
    }

    final protected function nonEmptyClause(Clauses\Clause $clause) {
        if ($clause->isEmpty()) return null;

        return $clause;
    }

    /**
     * @return Connection
     */
    protected function connectionOrThrow() {
        if (!$connection = $this->connection()) {
            throw new \LogicException('Db connection instance is not set in sql builder');
        }

        return $connection;
    }

    protected function condAnd(\VV\Db\Sql\Condition $condition, $field, $value = null) {
        if ($field) {
            if (is_array($field)) {
                $condition->and($field);
            } elseif ($field instanceof \VV\Db\Sql\Condition) {
                $condition->and($field);
            } else {
                if (func_num_args() < 3) $value = [];
                if (is_array($value)) {
                    $condition->and($field)->custom(...$value);
                } else {
                    $condition->and($field, $value);
                }
            }
        }

        return $this;
    }

    /**
     * @param array $map
     *
     * @return int
     */
    protected function makeNonEmptyClausesBitMask(array $map) {
        $res = 0;
        foreach ($map as $c => $clause) {
            if ($clause instanceof Clauses\Clause) {
                $add = !$clause->isEmpty();
            } else {
                $add = (bool)$clause;
            }

            if ($add) $res |= $c;
        }

        return $res;
    }

    /**
     * @param int|null             $flags
     * @param string|\Closure|null $decorator
     * @param int|null             $fetchSize
     *
     * @return \VV\Db\Result
     */
    protected function _result(int $flags = null, $decorator = null, int $fetchSize = null): \VV\Db\Result {
        return $this->connectionOrThrow()->query($this, null, $flags, $decorator, $fetchSize);
    }

    abstract protected function nonEmptyClausesMap(): array;
}
