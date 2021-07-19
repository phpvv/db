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

use JetBrains\PhpStorm\Pure;
use VV\Db\Connection;
use VV\Db\Model\Table;
use VV\Db\Result;
use VV\Db\Sql\Clauses\TableClause;
use VV\Db\Sql\Expressions\Expression;
use VV\Db\Statement;

/**
 * Class Query
 *
 * @package VV\Db\Sql
 */
abstract class Query
{
    private ?Connection $connection = null;
    private ?TableClause $tableClause = null;

    public function __construct(Connection $connection = null)
    {
        $this->setConnection($connection);
    }

    /**
     * @return Connection|null
     */
    public function getConnection(): ?Connection
    {
        return $this->connection;
    }

    /**
     * @param Connection|null $connection
     *
     * @return $this
     */
    public function setConnection(?Connection $connection): self
    {
        $this->connection = $connection;

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
    public function setMainTable(string|Table|Expression $table, string $alias = null): static
    {
        $this->getTableClause()->setMainTable($table, $alias);

        return $this;
    }

    /**
     * Add table alias
     *
     * @param string $alias
     *
     * @return $this
     */
    public function mainTableAs(string $alias): static
    {
        $this->getTableClause()->setMainTableAlias($alias);

        return $this;
    }

    /**
     * @return string
     */
    public function getMainTablePk(): string
    {
        $table = $this->getTableClause();
        if ($table->isEmpty()) {
            throw new \LogicException('Table not selected');
        }
        if (!$pk = $table->getMainTablePk()) {
            throw new \LogicException('Can\'t determine PK field');
        }

        return $pk;
    }

    public function getMainTableAlias(): string
    {
        $alias = $this->getTableClause()->getMainTableAlias();
        if (!$alias) {
            throw new \LogicException('Can\'t determine main table alias');
        }

        return $alias;
    }

    public function getLastTableAlias(): string
    {
        $alias = $this->getTableClause()->getLastTableAlias();
        if (!$alias) {
            throw new \LogicException('Can\'t determine last table alias');
        }

        return $alias;
    }

    /**
     * @param \Closure $callback
     *
     * @return $this
     */
    public function apply(\Closure $callback): static
    {
        $callback($this);

        return $this;
    }

    /**
     * @return int
     */
    public function getNonEmptyClausesIds(): int
    {
        $map = $this->getNonEmptyClausesMap();

        return $this->makeNonEmptyClausesBitMask($map);
    }

    /**
     * @return TableClause
     */
    public function getTableClause(): TableClause
    {
        if (!$this->tableClause) {
            $this->setTableClause($this->createTableClause());
        }

        return $this->tableClause;
    }

    /**
     * @param ?TableClause $tableClause
     *
     * @return $this
     */
    public function setTableClause(?TableClause $tableClause): static
    {
        $this->tableClause = $tableClause;

        return $this;
    }

    /**
     * Clears tableClause property and returns previous value
     *
     * @return TableClause
     */
    public function clearTableClause(): TableClause
    {
        try {
            return $this->getTableClause();
        } finally {
            $this->setTableClause(null);
        }
    }

    /**
     * @return TableClause
     */
    public function createTableClause(): TableClause
    {
        return new TableClause();
    }

    public function toString(&$params = null): string
    {
        return $this->connectionOrThrow()->stringifyQuery($this, $params);
    }

    /**
     * @return Statement
     */
    public function prepare(): Statement
    {
        return $this->connectionOrThrow()->prepare($this);
    }

    /**
     * @return Connection|null
     */
    protected function connectionOrThrow(): ?Connection
    {
        if (!$connection = $this->getConnection()) {
            throw new \LogicException('Db connection instance is not set in sql builder');
        }

        return $connection;
    }

    /**
     * @param array $map
     *
     * @return int
     */
    protected function makeNonEmptyClausesBitMask(array $map): int
    {
        $res = 0;
        foreach ($map as $c => $clause) {
            if ($clause instanceof Clauses\Clause) {
                $add = !$clause->isEmpty();
            } else {
                $add = (bool)$clause;
            }

            if ($add) {
                $res |= $c;
            }
        }

        return $res;
    }

    /**
     * @param int|null             $flags
     * @param string|\Closure|null $decorator
     * @param int|null             $fetchSize
     *
     * @return Result
     */
    protected function query(int $flags = null, string|\Closure $decorator = null, int $fetchSize = null): Result
    {
        return $this->connectionOrThrow()->query($this, null, $flags, $decorator, $fetchSize);
    }

    abstract protected function getNonEmptyClausesMap(): array;
}
