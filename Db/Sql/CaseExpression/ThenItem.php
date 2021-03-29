<?php declare(strict_types=1);

/*
 * This file is part of the VV package.
 *
 * (c) Volodymyr Sarnytskyi <v00v4n@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
namespace VV\Db\Sql\CaseExpression;

use VV\Db\Sql\Condition;
use VV\Db\Sql\Expression;

/**
 * Class Item
 *
 * @package VV\Db\Sql\CaseExpr
 */
class ThenItem {

    /**
     * @var Condition
     */
    private $condition;

    /**
     * @var Expression
     */
    private $comparisonExpr;

    /**
     * @var Expression
     */
    private $returnExpr;

    /**
     * ThenItem constructor.
     *
     * @param Condition  $condition
     * @param Expression $cmpExpr
     * @param Expression $returnExpr
     */
    public function __construct(Condition $condition = null, Expression $cmpExpr = null, Expression $returnExpr) {
        if (!$condition && !$cmpExpr) {
            throw new \InvalidArgumentException('$condition or $compareExpr must be non empty');
        }

        $this->condition = $condition;
        $this->comparisonExpr = $cmpExpr;
        $this->returnExpr = $returnExpr;
    }

    /**
     * @return Condition
     */
    public function condition() {
        return $this->condition;
    }

    /**
     * @return Expression
     */
    public function comparisonExpr() {
        return $this->comparisonExpr;
    }

    /**
     * @return Expression
     */
    public function returnExpr() {
        return $this->returnExpr;
    }
}
