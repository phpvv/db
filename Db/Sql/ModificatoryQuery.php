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

use VV\Db\Transaction;

/**
 * Class Modify
 *
 * @package VV\Db\Sql\Query
 *
 * @property-read int $affectedRows Execute query and return number of rows affected during query execute
 */
abstract class ModificatoryQuery extends \VV\Db\Sql\Query {

    protected ?Clauses\ReturnIntoClause $returnIntoClause = null;

    public function __get($var): mixed {
        return match ($var) {
            'affectedRows' => $this->affectedRows(),
            default => throw new \LogicException("Undefined property $var"),
        };
    }

    /**
     * Add `RETURNING INTO` clause (only for oracle)
     *
     * @param string|array|\Traversable|\VV\Db\Sql\Expressions\Expression $field
     * @param mixed|\VV\Db\Param                                          $param
     * @param int|null                                                    $type \VV\Db\P::T_...
     * @param string|null                                                 $name
     * @param int|null                                                    $size Size of variable in bytes
     *
     * @return $this
     */
    public function returnInto($field, &$param = null, $type = null, $name = null, $size = null): self {
        if ($field instanceof Clauses\ReturnIntoClause) {
            $this->setReturnIntoClause($field);
        } else {
            $this->returnIntoClause()->add($field, $param, $type, $name, $size);
        }

        return $this;
    }

    /**
     * Returns returnIntoClause
     *
     * @return Clauses\ReturnIntoClause
     */
    public function returnIntoClause(): Clauses\ReturnIntoClause {
        if (!$this->returnIntoClause) {
            $this->setReturnIntoClause($this->createReturnIntoClause());
        }

        return $this->returnIntoClause;
    }

    /**
     * Sets returnIntoClause
     *
     * @param Clauses\ReturnIntoClause|null $returnIntoClause
     *
     * @return $this
     */
    public function setReturnIntoClause(Clauses\ReturnIntoClause $returnIntoClause = null): self {
        $this->returnIntoClause = $returnIntoClause;

        return $this;
    }

    /**
     * Clears returnIntoClause property and returns previous value
     *
     * @return Clauses\ReturnIntoClause
     */
    public function clearReturnIntoClause(): Clauses\ReturnIntoClause {
        try {
            return $this->returnIntoClause();
        } finally {
            $this->setReturnIntoClause(null);
        }
    }

    /**
     * Creates default returnIntoClause
     *
     * @return Clauses\ReturnIntoClause
     */
    public function createReturnIntoClause(): Clauses\ReturnIntoClause {
        return new Clauses\ReturnIntoClause;
    }

    /**
     * Executes query
     *
     * @param Transaction|null $transaction
     *
     * @return \VV\Db\Result
     */
    public function exec(Transaction $transaction = null): \VV\Db\Result {
        if ($transaction) $this->setConnection($transaction->connection());
        elseif ($this->connection()->isInTransaction()) {
            throw new \LogicException('Statement execution outside current transaction');
        }

        return $this->_result();
    }

    /**
     * @param Transaction|null $transaction
     *
     * @return int
     */
    public function affectedRows(Transaction $transaction = null): int {
        return $this->exec($transaction)->affectedRows;
    }
}
