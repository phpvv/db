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
 * Class LikePredicate
 *
 * @package VV\Db\Sql\Predicates
 */
class LikePredicate extends PredicateBase
{
    private Expression $leftExpression;
    private Expression $rightExpression;
    private bool $caseInsensitive;

    /**
     * LikePredicate constructor.
     *
     * @param Expression $left
     * @param Expression $right
     * @param bool       $not
     * @param bool       $caseInsensitive
     */
    public function __construct(Expression $left, Expression $right, bool $not = false, bool $caseInsensitive = false)
    {
        parent::__construct($not);

        $this->leftExpression = $left;
        $this->rightExpression = $right;
        $this->caseInsensitive = $caseInsensitive;
    }

    /**
     * @return Expression
     */
    public function getLeftExpression(): Expression
    {
        return $this->leftExpression;
    }

    /**
     * @return Expression
     */
    public function getRightExpression(): Expression
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
