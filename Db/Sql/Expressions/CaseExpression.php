<?php declare(strict_types=1);

/*
 * This file is part of the VV package.
 *
 * (c) Volodymyr Sarnytskyi <v00v4n@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
namespace VV\Db\Sql\Expressions;

/**
 * Class CaseExpr
 *
 * @package VV\Db\Sql
 */
class CaseExpression implements Expression {

    use AliasFieldTrait;

    /**
     * @var CaseExpressionThenItem[]
     */
    private $thenItems = [];

    /**
     * @var Expression
     */
    private $mainExpr;

    /**
     * @var \VV\Db\Sql\Condition|Expression
     */
    private $when;

    /**
     * @var Expression
     */
    private $elseExpr;

    public function __construct($case = null) {
        if ($case) $this->case($case);
    }

    /**
     * @param string|Expression $case
     *
     * @return $this
     */
    public function case(string|Expression $case): self {
        $this->mainExpr = \VV\Db\Sql::expression($case);

        return $this;
    }

    /**
     * @param $when
     *
     * @return $this
     */
    public function when($when): self {
        if ($this->mainExpr) {
            $when = \VV\Db\Sql::expression($when);
        } else {
            $when = \VV\Db\Sql::condition($when);
        }

        $this->when = $when;

        return $this;
    }

    /**
     * @param $then
     *
     * @return $this
     */
    public function then($then): self {
        $then = \VV\Db\Sql::expression($then);
        if ($this->mainExpr) {
            $thenItem = $this->createComparisonThenItem($this->when, $then);
        } else {
            $thenItem = $this->createSearchThenItem($this->when, $then);
        }

        return $this->addThenItem($thenItem);
    }

    /**
     * @param $else
     *
     * @return $this
     */
    public function else($else): self {
        $this->elseExpr = \VV\Db\Sql::expression($else);

        return $this;
    }

    /**
     * @param CaseExpressionThenItem $thenItem
     *
     * @return $this
     */
    public function addThenItem(CaseExpressionThenItem $thenItem): self {
        $this->thenItems[] = $thenItem;
        $this->when = null;

        return $this;
    }

    /**
     * @return Expression|null
     */
    public function mainExpr(): ?Expression {
        return $this->mainExpr;
    }

    /**
     * @return CaseExpressionThenItem[]
     */
    public function thenItems(): array {
        return $this->thenItems;
    }

    /**
     * @return Expression|null
     */
    public function elseExpr(): ?Expression {
        return $this->elseExpr;
    }

    /**
     * @param array $conditions
     *
     * @return $this
     */
    public function buildOrderBy(array $conditions): self {
        $order = 1;
        foreach ($conditions as $condition) {
            $this->when($condition)->then($order++);
        }

        return $this->else($order);
    }

    /**
     * @param \VV\Db\Sql\Condition $when
     * @param Expression           $then
     *
     * @return CaseExpressionThenItem
     */
    public function createSearchThenItem(\VV\Db\Sql\Condition $when, Expression $then): CaseExpressionThenItem {
        return new CaseExpressionThenItem($when, null, $then);
    }

    /**
     * @param Expression $when
     * @param Expression $then
     *
     * @return CaseExpressionThenItem
     */
    public function createComparisonThenItem(Expression $when, Expression $then): CaseExpressionThenItem {
        return new CaseExpressionThenItem(null, $when, $then);
    }

    /**
     * @return string
     */
    public function exprId(): string {
        return spl_object_hash($this);
    }
}
