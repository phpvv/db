<?php

declare(strict_types=1);

/*
 * This file is part of the VV package.
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
     * @param string|int|Expression|Predicate|array|null $expression
     * @param mixed|array|Expression|null                $param
     *
     * @return $this
     */
    public function where(string|int|Expression|Predicate|array|null $expression, mixed $param = null): static
    {
        return $this->conditionAnd($this->getWhereClause(), ...func_get_args());
    }

    /**
     * Add `WHERE pk_column=`
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
     * @param string|int|Expression $expression
     * @param mixed|Expression      ...$values
     *
     * @return $this
     */
    public function whereIn(string|int|Expression $expression, ...$values): static
    {
        $this->getWhereClause()->and($expression)->in(...$values);

        return $this;
    }

    /**
     * @param string|int|Expression $expression
     * @param mixed|Expression      ...$values
     *
     * @return $this
     */
    public function whereNotIn(string|int|Expression $expression, ...$values): static
    {
        $this->getWhereClause()->and($expression)->not->in(...$values);

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
     * @param string|int|Expression $expression
     * @param mixed                 $from
     * @param mixed                 $till
     *
     * @return $this
     */
    public function whereBetween(string|int|Expression $expression, mixed $from, mixed $till): static
    {
        $this->getWhereClause()->and($expression)->between($from, $till);

        return $this;
    }

    /**
     * @param string|int|Expression $expression
     * @param mixed                 $from
     * @param mixed                 $till
     *
     * @return $this
     */
    public function whereNotBetween(string|int|Expression $expression, mixed $from, mixed $till): static
    {
        $this->getWhereClause()->and($expression)->not->between($from, $till);

        return $this;
    }

    /**
     * @param string|int|Expression $expression
     * @param string                $pattern
     * @param bool                  $caseInsensitive
     *
     * @return $this
     */
    public function whereLike(
        string|int|Expression $expression,
        string $pattern,
        bool $caseInsensitive = false
    ): static {
        $this->getWhereClause()->and($expression)->like($pattern, $caseInsensitive);

        return $this;
    }

    /**
     * @param string|int|Expression $expression
     * @param string                $pattern
     * @param bool                  $caseInsensitive
     *
     * @return $this
     */
    public function whereNotLike(
        string|int|Expression $expression,
        string $pattern,
        bool $caseInsensitive = false
    ): static {
        $this->getWhereClause()->and($expression)->not->like($pattern, $caseInsensitive);

        return $this;
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
     * @return Condition
     */
    public function createWhereClause(): Condition
    {
        return Sql::condition();
    }

    protected function conditionAnd(
        Condition $condition,
        string|int|Expression|Predicate|array|null $expression,
        mixed $param = null
    ): static {
        if ($expression === null) {
            return $this;
        }

        if (is_array($expression)) {
            $condition->and($expression);
        } elseif ($expression instanceof Predicate) {
            $condition->and($expression);
        } elseif (func_num_args() < 3) {
            $condition->and($expression)->custom();
        } else {
            $condition->and($expression, $param);
        }

        return $this;
    }
}
