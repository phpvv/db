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
 * Class IsNullPredicate
 *
 * @package VV\Db\Sql\Predicates
 */
class IsNullPredicate extends PredicateBase
{

    private Expression $expression;

    /**
     * IsNullPredicate constructor.
     *
     * @param Expression $expression
     * @param bool       $not
     */
    public function __construct(Expression $expression, bool $not = false)
    {
        parent::__construct($not);

        $this->expression = $expression;
    }

    /**
     * @return Expression
     */
    public function getExpression(): Expression
    {
        return $this->expression;
    }
}
