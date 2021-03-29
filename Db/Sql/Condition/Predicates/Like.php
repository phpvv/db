<?php declare(strict_types=1);

/*
 * This file is part of the VV package.
 *
 * (c) Volodymyr Sarnytskyi <v00v4n@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
namespace VV\Db\Sql\Condition\Predicates;

use VV\Db\Sql\Expression;

/**
 * Class Like
 *
 * @package VV\Db\Sql\Predicate
 */
class Like extends Base {

    private Expression $leftExpr;
    private Expression $rightExpr;
    private bool $caseInsensitive;

    /**
     * Like constructor.
     *
     * @param Expression $leftExpr
     * @param Expression $rightExpr
     * @param bool       $not
     * @param bool       $caseInsensitive
     */
    public function __construct(Expression $leftExpr, Expression $rightExpr, bool $not = false, bool $caseInsensitive = false) {
        $this->leftExpr = $leftExpr;
        $this->rightExpr = $rightExpr;
        $this->not = $not;
        $this->caseInsensitive = $caseInsensitive;
    }

    /**
     * @return Expression
     * @return Expression
     */
    public function leftExpr(): Expression {
        return $this->leftExpr;
    }

    /**
     * @return Expression
     */
    public function rightExpr(): Expression {
        return $this->rightExpr;
    }

    /**
     * @return bool
     */
    public function isCaseInsensitive(): bool {
        return $this->caseInsensitive;
    }
}
