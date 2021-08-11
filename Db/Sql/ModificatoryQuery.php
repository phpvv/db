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

use VV\Db\Param;
use VV\Db\Result;
use VV\Db\Sql\Clauses\ColumnsClause;
use VV\Db\Sql\Clauses\ReturnIntoClause;
use VV\Db\Sql\Expressions\Expression;
use VV\Db\Transaction;

/**
 * Class Statement
 *
 * @package VV\Db\Sql
 * @property-read int $affectedRows Execute query and return number of rows affected during query execute
 */
abstract class ModificatoryQuery extends Query
{
    public const C_RETURN_INTO = 0x100,
        C_RETURNING = 0x110;

    protected ?ReturnIntoClause $returnIntoClause = null;
    protected ?ColumnsClause $returningClause = null;

    public function __get($var): mixed
    {
        return match ($var) {
            'affectedRows' => $this->affectedRows(),
            default => throw new \LogicException("Undefined property $var"),
        };
    }

    /**
     * Add `RETURNING column INTO :param` clause (only for oracle)
     *
     * @param string|iterable|Expression $field
     * @param mixed|Param                $param
     * @param int|null                   $type \VV\Db\P::T_...
     * @param string|null                $name
     * @param int|null                   $size Size of variable in bytes
     *
     * @return $this
     */
    public function returnInto(
        string|iterable|Expression $field,
        mixed &$param = null,
        int $type = null,
        string $name = null,
        int $size = null
    ): static {
        $this->getReturnIntoClause()->add($field, $param, $type, $name, $size);

        return $this;
    }

    /**
     * Add `RETURNING column1, column2, ...` clause (only for postgres)
     *
     * @param string|array|Expression ...$columns
     *
     * @return $this
     */
    public function returning(string|array|Expression ...$columns): static
    {
        $this->getReturningClause()->add(...$columns);

        return $this;
    }

    /**
     * Returns returnIntoClause
     *
     * @return ReturnIntoClause
     */
    public function getReturnIntoClause(): ReturnIntoClause
    {
        if (!$this->returnIntoClause) {
            $this->setReturnIntoClause($this->createReturnIntoClause());
        }

        return $this->returnIntoClause;
    }

    /**
     * Sets returnIntoClause
     *
     * @param ReturnIntoClause|null $returnIntoClause
     *
     * @return $this
     */
    public function setReturnIntoClause(?ReturnIntoClause $returnIntoClause): static
    {
        $this->returnIntoClause = $returnIntoClause;

        return $this;
    }

    /**
     * Clears returnIntoClause property and returns previous value
     *
     * @return ReturnIntoClause
     */
    public function clearReturnIntoClause(): ReturnIntoClause
    {
        try {
            return $this->getReturnIntoClause();
        } finally {
            $this->setReturnIntoClause(null);
        }
    }

    /**
     * Creates default returnIntoClause
     *
     * @return ReturnIntoClause
     */
    public function createReturnIntoClause(): ReturnIntoClause
    {
        return new ReturnIntoClause();
    }

    /**
     * Returns returningClause
     *
     * @return ColumnsClause
     */
    public function getReturningClause(): ColumnsClause
    {
        if (!$this->returningClause) {
            $this->setReturningClause($this->createReturningClause());
        }

        return $this->returningClause;
    }

    /**
     * Sets returningClause
     *
     * @param ColumnsClause|null $returningClause
     *
     * @return $this
     */
    public function setReturningClause(?ColumnsClause $returningClause): static
    {
        $this->returningClause = $returningClause;

        return $this;
    }

    /**
     * Clears returningClause property and returns previous value
     *
     * @return ColumnsClause
     */
    public function clearReturningClause(): ColumnsClause
    {
        try {
            return $this->getReturningClause();
        } finally {
            $this->setReturningClause(null);
        }
    }

    /**
     * Creates default returningClause
     *
     * @return ColumnsClause
     */
    public function createReturningClause(): ColumnsClause
    {
        return (new ColumnsClause())->setAsteriskOnEmpty(false);
    }

    /**
     * Executes query
     *
     * @param Transaction|null $transaction
     *
     * @return Result
     */
    public function exec(Transaction $transaction = null): Result
    {
        if ($transaction) {
            $this->setConnection($transaction->getConnection());
        } elseif ($this->getConnection()->isInTransaction()) {
            throw new \LogicException('Statement execution outside current transaction');
        }

        return $this->query();
    }

    /**
     * @param Transaction|null $transaction
     *
     * @return int
     */
    public function affectedRows(Transaction $transaction = null): int
    {
        return $this->exec($transaction)->affectedRows();
    }

    protected function getNonEmptyClausesMap(): array
    {
        return [
            self::C_RETURN_INTO => $this->getReturnIntoClause(),
            self::C_RETURNING => $this->getReturningClause(),
        ];
    }
}
