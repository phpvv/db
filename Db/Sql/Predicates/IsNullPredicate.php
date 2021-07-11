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
     * IsNull constructor.
     *
     * @param Expression $expression
     * @param bool       $not
     */
    public function __construct(Expression $expression, bool $not = false)
    {
        $this->expression = $expression;
        $this->not = $not;
    }

    /**
     * @return Expression
     */
    public function expression(): Expression
    {
        return $this->expression;
    }
}
