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
 * Class Like
 *
 * @package VV\Db\Sql\Predicate
 */
class LikePredicate extends PredicateBase
{

    private Expression $leftExpression;
    private Expression $rightExpression;
    private bool $caseInsensitive;

    /**
     * Like constructor.
     *
     * @param Expression $left
     * @param Expression $right
     * @param bool       $not
     * @param bool       $caseInsensitive
     */
    public function __construct(Expression $left, Expression $right, bool $not = false, bool $caseInsensitive = false)
    {
        $this->leftExpression = $left;
        $this->rightExpression = $right;
        $this->not = $not;
        $this->caseInsensitive = $caseInsensitive;
    }

    /**
     * @return Expression
     */
    public function leftExpression(): Expression
    {
        return $this->leftExpression;
    }

    /**
     * @return Expression
     */
    public function rightExpression(): Expression
    {
        return $this->rightExpression;
    }

    /**
     * @return bool
     */
    public function isCaseInsensitive(): bool
    {
        return $this->caseInsensitive;
    }
}
