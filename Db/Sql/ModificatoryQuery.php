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
     * @param string|iterable|Expression $expression
     * @param mixed|Param                $param
     * @param int|null                   $type \VV\Db\P::T_...
     * @param string|null                $name
     * @param int|null                   $size Size of variable in bytes
     *
     * @return $this
     */
    public function returnInto(
        string|iterable|Expression $expression,
        mixed &$param = null,
        int $type = null,
        string $name = null,
        int $size = null
    ): static {
        $this->getReturnIntoClause()->add($expression, $param, $type, $name, $size);

        return $this;
    }

    /**
     * Add `RETURNING column1, column2, ...` clause (only for postgres)
     */
    public function returning(string|array|Expression ...$columns): static
    {
        $this->getReturningClause()->add(...$columns);

        return $this;
    }

    /**
     * Returns ReturnIntoClause
     */
    public function getReturnIntoClause(): ReturnIntoClause
    {
        if (!$this->returnIntoClause) {
            $this->setReturnIntoClause($this->createReturnIntoClause());
        }

        return $this->returnIntoClause;
    }

    /**
     * Sets ReturnIntoClause
     */
    public function setReturnIntoClause(?ReturnIntoClause $returnIntoClause): static
    {
        $this->returnIntoClause = $returnIntoClause;

        return $this;
    }

    /**
     * Creates ReturnIntoClause
     */
    public function createReturnIntoClause(): ReturnIntoClause
    {
        return new ReturnIntoClause();
    }

    /**
     * Returns ReturningClause
     */
    public function getReturningClause(): ColumnsClause
    {
        if (!$this->returningClause) {
            $this->setReturningClause($this->createReturningClause());
        }

        return $this->returningClause;
    }

    /**
     * Sets ReturningClause
     */
    public function setReturningClause(?ColumnsClause $returningClause): static
    {
        $this->returningClause = $returningClause;

        return $this;
    }

    /**
     * Creates ReturningClause
     */
    public function createReturningClause(): ColumnsClause
    {
        return (new ColumnsClause())->setAsteriskOnEmpty(false);
    }

    /**
     * Executes query
     */
    public function exec(Transaction $transaction = null): Result
    {
        if ($transaction) {
            $this->setConnection($transaction->getConnection());
        } elseif ($this->getConnection()->isInTransaction()) {
            throw new \LogicException('Statement execution outside current transaction');
        }

        return $this->getConnectionOrThrow()->query($this);
    }

    /**
     * Executes(!) query and returns number of affected rows
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
