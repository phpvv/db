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
use VV\Db\Param;
use VV\Db\Result;
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

    protected ?ReturnIntoClause $returnIntoClause = null;

    public function __get($var): mixed
    {
        return match ($var) {
            'affectedRows' => $this->affectedRows(),
            default => throw new \LogicException("Undefined property $var"),
        };
    }

    /**
     * Add `RETURNING INTO` clause (only for oracle)
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
        $this->returnIntoClause()->add($field, $param, $type, $name, $size);

        return $this;
    }

    /**
     * Returns returnIntoClause
     *
     * @return ReturnIntoClause
     */
    public function returnIntoClause(): ReturnIntoClause
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
            return $this->returnIntoClause();
        } finally {
            $this->setReturnIntoClause(null);
        }
    }

    /**
     * Creates default returnIntoClause
     *
     * @return ReturnIntoClause
     */
    #[Pure]
    public function createReturnIntoClause(): ReturnIntoClause
    {
        return new ReturnIntoClause();
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
        } elseif ($this->connection()->isInTransaction()) {
            throw new \LogicException('Statement execution outside current transaction');
        }

        return $this->_result();
    }

    /**
     * @param Transaction|null $transaction
     *
     * @return int
     */
    public function affectedRows(Transaction $transaction = null): int
    {
        return $this->exec($transaction)->affectedRows;
    }
}
