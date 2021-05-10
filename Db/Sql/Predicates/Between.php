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
 * Class Between
 *
 * @package VV\Db\Sql\Predicate
 */
class Between extends Base {

    /**
     * @var Expression
     */
    private $expr;

    /**
     * @var \VV\Db\Sql\Expressions\Expression
     */
    private $from;

    /**
     * @var Expression
     */
    private $till;

    /**
     * Between constructor.
     *
     * @param Expression $expr
     * @param Expression $from
     * @param Expression $till
     * @param bool       $not
     */
    public function __construct(Expression $expr, Expression $from, Expression $till, bool $not = false) {
        $this->expr = $expr;
        $this->from = $from;
        $this->till = $till;
        $this->not = $not;
    }

    /**
     * @return Expression
     */
    public function expr(): Expression {
        return $this->expr;
    }

    /**
     * @return \VV\Db\Sql\Expressions\Expression
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
