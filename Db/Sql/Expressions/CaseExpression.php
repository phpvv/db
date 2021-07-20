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

use VV\Db\Sql;
use VV\Db\Sql\Condition;
use VV\Db\Sql\Predicates\Predicate;

/**
 * Class CaseExpr
 *
 * @package VV\Db\Sql
 */
class CaseExpression implements Expression
{
    use AliasFieldTrait;

    private ?Expression $mainExpression = null;
    /** @var CaseExpressionThenItem[] */
    private array $thenItems = [];
    private Expression|Condition|null $when = null;
    private ?Expression $elseExpression = null;

    /**
     * CaseExpression constructor.
     *
     * @param string|int|Expression|null $case
     */
    public function __construct(string|int|Expression $case = null)
    {
        if ($case !== null) {
            $this->case($case);
        }
    }

    /**
     * @param string|int|Expression $case
     *
     * @return $this
     */
    public function case(string|int|Expression $case): static
    {
        $this->mainExpression = Sql::expression($case);

        return $this;
    }

    /**
     * @param string|int|Expression|Predicate|array $when
     *
     * @return $this
     */
    public function when(string|int|Expression|Predicate|array $when): static
    {
        if ($this->mainExpression) {
            $when = Sql::expression($when);
        } else {
            $when = Sql::condition($when);
        }

        $this->when = $when;

        return $this;
    }

    /**
     * @param string|int|Expression $then
     *
     * @return $this
     */
    public function then(string|int|Expression $then): static
    {
        $then = Sql::expression($then);
        if ($this->mainExpression) {
            $thenItem = $this->createComparisonThenItem($this->when, $then);
        } else {
            $thenItem = $this->createSearchThenItem($this->when, $then);
        }

        return $this->addThenItem($thenItem);
    }

    /**
     * @param string|int|Expression $else
     *
     * @return $this
     */
    public function else(string|int|Expression $else): static
    {
        $this->elseExpression = Sql::expression($else);

        return $this;
    }

    /**
     * @param CaseExpressionThenItem $thenItem
     *
     * @return $this
     */
    public function addThenItem(CaseExpressionThenItem $thenItem): static
    {
        $this->thenItems[] = $thenItem;
        $this->when = null;

        return $this;
    }

    /**
     * @return Expression|null
     */
    public function getMainExpression(): ?Expression
    {
        return $this->mainExpression;
    }

    /**
     * @return CaseExpressionThenItem[]
     */
    public function getThenItems(): array
    {
        return $this->thenItems;
    }

    /**
     * @return Expression|null
     */
    public function getElseExpression(): ?Expression
    {
        return $this->elseExpression;
    }

    /**
     * @param array $conditions
     *
     * @return $this
     */
    public function buildOrderBy(array $conditions): static
    {
        $order = 1;
        foreach ($conditions as $condition) {
            $this->when($condition)->then($order++);
        }

        return $this->else($order);
    }

    /**
     * @return string
     */
    public function getExpressionId(): string
    {
        return spl_object_hash($this);
    }

    /**
     * @param Condition  $when
     * @param Expression $then
     *
     * @return CaseExpressionThenItem
     */
    protected function createSearchThenItem(Condition $when, Expression $then): CaseExpressionThenItem
    {
        return new CaseExpressionThenItem($when, null, $then);
    }

    /**
     * @param Expression $when
     * @param Expression $then
     *
     * @return CaseExpressionThenItem
     */
    protected function createComparisonThenItem(Expression $when, Expression $then): CaseExpressionThenItem
    {
        return new CaseExpressionThenItem(null, $when, $then);
    }
}
