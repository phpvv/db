<?php declare(strict_types=1);

/*
 * This file is part of the VV package.
 *
 * (c) Volodymyr Sarnytskyi <v00v4n@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
namespace VV\Db\Sql\Predicates;

use VV\Db\Sql\Expressions\Expression;

/**
 * Class BetweenPredicate
 *
 * @package VV\Db\Sql\Predicates
 */
class BetweenPredicate extends PredicateBase {

    private Expression $expression;
    private Expression $from;
    private Expression $till;

    /**
     * Between constructor.
     *
     * @param Expression $expression
     * @param Expression $from
     * @param Expression $till
     * @param bool       $not
     */
    public function __construct(Expression $expression, Expression $from, Expression $till, bool $not = false) {
        $this->expression = $expression;
        $this->from = $from;
        $this->till = $till;
        $this->not = $not;
    }

    /**
     * @return Expression
     */
    public function expression(): Expression {
        return $this->expression;
    }

    /**
     * @return Expression
     */
    public function from(): Expression {
        return $this->from;
    }

    /**
     * @return Expression
     */
    public function till(): Expression {
        return $this->till;
    }
}
