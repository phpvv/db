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

use VV\Db\Connection;
use VV\Db\Model\Table;
use VV\Db\Param;
use VV\Db\Result;
use VV\Db\Sql\Clauses\QueryDatasetTrait;
use VV\Db\Sql\Expressions\Expression;
use VV\Db\Transaction;

/**
 * Class InsertQuery
 *
 * @package VV\Db\Sql
 *
 * @property-read mixed $insertedId     Execute query and return last insert id
 */
class InsertQuery extends ModificatoryQuery
{
    use QueryDatasetTrait;

    public const C_DATASET = 0x01,
        C_COLUMNS = 0x02,
        C_VALUES = 0x04,
        C_ON_DUPLICATE_KEY = 0x08,
        C_RETURN_INSERTED_ID = 0x10;

    private ?Clauses\InsertColumnsClause $columnsClause = null;
    private ?Clauses\InsertValuesClause $valuesClause = null;
    private ?Clauses\DatasetClause $onDuplicateKeyClause = null;
    private ?Clauses\InsertedIdClause $insertedIdClause = null;

    private int $execPerCount = 0;

    public function __get($var): mixed
    {
        return match ($var) {
            'insertedId' => $this->insertedId(),
            default => parent::__get($var),
        };
    }

    /**
     * Add `INTO` clause in sql
     *
     * @param string|Table $table
     * @param string|null  $alias
     *
     * @return $this
     */
    public function into(string|Table $table, string $alias = null): static
    {
        return $this->setMainTable($table, $alias);
    }

    /**
     * @param string|Expression|Clauses\InsertColumnsClause ...$columns
     *
     * @return $this
     */
    public function columns(...$columns): static
    {
        $clause = $columns[0] instanceof Clauses\InsertColumnsClause
            ? $columns[0]
            : $this->createColumnsClause()->add(...$columns);

        return $this->setColumnsClause($clause);
    }

    /**
     * Set VALUES
     *
     * @param string|Expression|Param ...$values
     *
     * @return $this
     */
    public function values(...$values): static
    {
        if ($values[0] instanceof SelectQuery) {
            $this->getValuesClause()->clear()->add($values[0]);
        } else {
            $this->getValuesClause()->add(...$values);
        }

        $this->perExec();

        return $this;
    }

    /**
     * Add on duplicate key update clause
     *
     * @param string|Expression $column
     * @param mixed             $value
     *
     * @return $this
     */
    public function onDuplicateKey(Expression|string $column, mixed $value = null): static
    {
        $this->getOnDuplicateKeyClause()->add(...func_get_args());

        return $this;
    }

    /**
     * Returns columnsClause
     *
     * @return Clauses\InsertColumnsClause
     */
    public function getColumnsClause(): Clauses\InsertColumnsClause
    {
        if (!$this->columnsClause) {
            $this->setColumnsClause($this->createColumnsClause());
        }

        return $this->columnsClause;
    }

    /**
     * Sets columnsClause
     *
     * @param Clauses\InsertColumnsClause|null $columnsClause
     *
     * @return $this
     */
    public function setColumnsClause(?Clauses\InsertColumnsClause $columnsClause): static
    {
        $this->columnsClause = $columnsClause;

        return $this;
    }

    /**
     * Creates default columnsClause
     *
     * @return Clauses\InsertColumnsClause
     */
    public function createColumnsClause(): Clauses\InsertColumnsClause
    {
        return new Clauses\InsertColumnsClause();
    }

    /**
     * Returns valuesClause
     *
     * @return Clauses\InsertValuesClause
     */
    public function getValuesClause(): Clauses\InsertValuesClause
    {
        if (!$this->valuesClause) {
            $this->setValuesClause($this->createValuesClause());
        }

        return $this->valuesClause;
    }

    /**
     * Sets valuesClause
     *
     * @param Clauses\InsertValuesClause|null $valuesClause
     *
     * @return $this
     */
    public function setValuesClause(?Clauses\InsertValuesClause $valuesClause): static
    {
        $this->valuesClause = $valuesClause;

        return $this;
    }

    /**
     * Creates default valuesClause
     *
     * @return Clauses\InsertValuesClause
     */
    public function createValuesClause(): Clauses\InsertValuesClause
    {
        return new Clauses\InsertValuesClause();
    }

    /**
     * Returns onDupKeyClause
     *
     * @return Clauses\DatasetClause
     */
    public function getOnDuplicateKeyClause(): Clauses\DatasetClause
    {
        if (!$this->onDuplicateKeyClause) {
            $this->setOnDuplicateKeyClause($this->createOnDuplicateKeyClause());
        }

        return $this->onDuplicateKeyClause;
    }

    /**
     * Sets onDupKeyClause
     *
     * @param Clauses\DatasetClause|null $onDuplicateKeyClause
     *
     * @return $this
     */
    public function setOnDuplicateKeyClause(?Clauses\DatasetClause $onDuplicateKeyClause): static
    {
        $this->onDuplicateKeyClause = $onDuplicateKeyClause;

        return $this;
    }

    /**
     * Clears onDupKeyClause property and returns previous value
     *
     * @return Clauses\DatasetClause
     */
    public function clearOnDuplicateKeyClause(): Clauses\DatasetClause
    {
        try {
            return $this->getOnDuplicateKeyClause();
        } finally {
            $this->setOnDuplicateKeyClause(null);
        }
    }

    /**
     * Creates default onDupKeyClause
     *
     * @return Clauses\DatasetClause
     */
    public function createOnDuplicateKeyClause(): Clauses\DatasetClause
    {
        return new Clauses\DatasetClause();
    }

    /**
     * Returns insertedIdClause
     *
     * @return Clauses\InsertedIdClause
     */
    public function getInsertedIdClause(): Clauses\InsertedIdClause
    {
        if (!$this->insertedIdClause) {
            $this->setInsertedIdClause($this->createInsertedIdClause());
        }

        return $this->insertedIdClause;
    }

    /**
     * Creates default insertedIdClause
     *
     * @return Clauses\InsertedIdClause
     */
    public function createInsertedIdClause(): Clauses\InsertedIdClause
    {
        return new Clauses\InsertedIdClause();
    }

    /**
     * @param $count
     *
     * @return $this
     */
    public function execPer($count): static
    {
        $this->execPerCount = $count;

        return $this;
    }

    /**
     * @return int
     */
    public function getExecPerCount(): int
    {
        return $this->execPerCount;
    }

    /**
     * @return Result|null
     */
    public function execPerFinish(): ?Result
    {
        return $this->perExec(true);
    }

    /**
     * Executes(!) query and returns insertedId
     */
    public function insertedId(Connection|Transaction $connection = null): int|string
    {
        $insertedIdClause = $this->getInsertedIdClause();
        if ($insertedIdClause->isEmpty()) {
            $insertedIdClause->set();
        }

        return $this->exec($connection)->insertedId();
    }

    /**
     *  Inits InsertedIdClause
     *
     * @param int|Param|null $type
     * @param int|null       $size
     * @param string|null    $pk
     *
     * @return $this
     */
    public function initInsertedId(Param|int $type = null, int $size = null, string $pk = null): static
    {
        $this->getInsertedIdClause()->set($type, $size, $pk);

        return $this;
    }

    /**
     * Sets insertedIdClause
     *
     * @param Clauses\InsertedIdClause|null $clause
     *
     * @return $this
     */
    protected function setInsertedIdClause(Clauses\InsertedIdClause $clause = null): static
    {
        $this->insertedIdClause = $clause;

        return $this;
    }

    protected function getNonEmptyClausesMap(): array
    {
        return parent::getNonEmptyClausesMap()
               + [
                   self::C_DATASET => $this->getDatasetClause(),
                   self::C_COLUMNS => $this->getColumnsClause(),
                   self::C_VALUES => $this->getValuesClause(),
                   self::C_ON_DUPLICATE_KEY => $this->getOnDuplicateKeyClause(),
                   self::C_RETURN_INTO => $this->getReturnIntoClause(),
                   self::C_RETURN_INSERTED_ID => $this->getInsertedIdClause(),
               ];
    }

    /**
     * @param bool $finish
     *
     * @return Result|null
     */
    private function perExec(bool $finish = false): ?Result
    {
        if (!$epc = $this->getExecPerCount()) {
            return null;
        }

        $valuesClause = $this->getValuesClause();
        $cnt = count($valuesClause->getItems());

        if (!$cnt) {
            return null;
        }
        if (!$finish && $cnt < $epc) {
            return null;
        }

        try {
            return $this->exec();
        } finally {
            $this->setValuesClause(null);
        }
    }
}
