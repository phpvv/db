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

namespace VV\Db\Sql\Predicates;

use VV\Db\Sql\Expressions\Expression;

/**
 * Class BetweenPredicate
 *
 * @package VV\Db\Sql\Predicates
 */
class BetweenPredicate extends PredicateBase
{
    private Expression $expression;
    private Expression $fromExpression;
    private Expression $tillExpression;

    /**
     * BetweenPredicate constructor.
     *
     * @param Expression $expression
     * @param Expression $from
     * @param Expression $till
     * @param bool       $not
     */
    public function __construct(Expression $expression, Expression $from, Expression $till, bool $not = false)
    {
        parent::__construct($not);

        $this->expression = $expression;
        $this->fromExpression = $from;
        $this->tillExpression = $till;
    }

    /**
     * @return Expression
     */
    public function getExpression(): Expression
    {
        return $this->expression;
    }

    /**
     * @return Expression
     */
    public function getFromExpression(): Expression
    {
        return $this->fromExpression;
    }

    /**
     * @return Expression
     */
    public function getTillExpression(): Expression
    {
        return $this->tillExpression;
    }
}
