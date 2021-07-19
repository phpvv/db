<?php

declare(strict_types=1);

/*
 * This file is part of the phpvv package.
 *
 * (c) Volodymyr Sarnytskyi <v00v4n@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
namespace VV\Db\Sql\Clauses;

use VV\Db\Sql;
use VV\Db\Sql\Condition;
use VV\Db\Sql\Expressions\Expression;
use VV\Db\Sql\Predicates\Predicate;

/**
 * Trait QueryWhereTrait
 *
 * @package VV\Db\Sql\Clauses
 */
trait QueryWhereTrait
{
    private ?Condition $whereClause = null;

    /**
     * Add `WHERE` clause
     *
     * @param string|int|array|Expression|Predicate|null $field
     * @param mixed|array|Expression|null                $value
     *
     * @return $this
     */
    public function where(string|int|Expression|array|Predicate|null $field, mixed $value = null): static
    {
        return $this->condintionAnd($this->getWhereClause(), ...func_get_args());
    }

    /**
     * Add `WHERE pk_field=`
     *
     *
     * @param string|int|Expression $id
     *
     * @return $this
     */
    public function whereId(string|int|Expression $id): static
    {
        $this->where($this->getMainTablePk(), $id);

        return $this;
    }

    /**
     * @param string|int|Expression $field
     * @param mixed|Expression      ...$values
     *
     * @return $this
     */
    public function whereIn(string|int|Expression $field, ...$values): static
    {
        $this->getWhereClause()->and($field)->in(...$values);

        return $this;
    }

    /**
     * @param string|int|Expression $field
     * @param mixed|Expression      ...$values
     *
     * @return $this
     */
    public function whereNotIn(string|int|Expression $field, ...$values): static
    {
        $this->getWhereClause()->and($field)->not->in(...$values);

        return $this;
    }

    /**
     * @param mixed|Expression ...$values
     *
     * @return $this
     */
    public function whereIdIn(mixed ...$values): static
    {
        return $this->whereIn($this->getMainTablePk(), ...$values);
    }

    /**
     * @param mixed|Expression ...$values
     *
     * @return $this
     */
    public function whereIdNotIn(mixed ...$values): static
    {
        return $this->whereNotIn($this->getMainTablePk(), ...$values);
    }

    /**
     * @return Condition
     */
    public function getWhereClause(): Condition
    {
        if (!$this->whereClause) {
            $this->setWhereClause($this->createWhereClause());
        }

        return $this->whereClause;
    }

    /**
     * @param Condition|null $whereClause
     *
     * @return $this
     */
    public function setWhereClause(?Condition $whereClause): static
    {
        $this->whereClause = $whereClause;

        return $this;
    }

    /**
     * Clears whereClause property and returns previous value
     *
     * @return Condition
     */
    public function clearWhereClause(): Condition
    {
        try {
            return $this->getWhereClause();
        } finally {
            $this->setWhereClause(null);
        }
    }

    /**
     * @return Condition
     */
    public function createWhereClause(): Condition
    {
        return Sql::condition();
    }

    protected function condintionAnd(
        Condition $condition,
        string|int|Expression|array|Predicate|null $field,
        mixed $value = null
    ): static {
        if ($field) {
            if (is_array($field)) {
                $condition->and($field);
            } elseif ($field instanceof Predicate) {
                $condition->and($field);
            } else {
                if (func_num_args() < 3) {
                    $value = [];
                }
                if (is_array($value)) {
                    $condition->and($field)->custom(...$value);
                } else {
                    $condition->and($field, $value);
                }
            }
        }

        return $this;
    }
}
