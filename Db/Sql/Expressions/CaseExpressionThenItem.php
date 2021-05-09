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

use VV\Db\Sql\Condition;

/**
 * Class Item
 *
 * @package VV\Db\Sql\CaseExpr
 */
class CaseExpressionThenItem {

    private ?Condition $whenCondition;
    private ?Expression $whenExpression;
    private Expression $thenExpression;

    /**
     * ThenItem constructor.
     *
     * @param Condition|null  $whenCondition
     * @param Expression|null $whenExpression
     * @param Expression      $thenExpression
     */
    public function __construct(?Condition $whenCondition, ?Expression $whenExpression, Expression $thenExpression) {
        if (!$whenCondition && !$whenExpression) {
            throw new \InvalidArgumentException('$whenCondition or $whenExpression must be non empty');
        }

        $this->whenCondition = $whenCondition;
        $this->whenExpression = $whenExpression;
        $this->thenExpression = $thenExpression;
    }

    /**
     * @return Condition|null
     */
    public function whenCondition(): ?Condition {
        return $this->whenCondition;
    }

    /**
     * @return Expression|null
     */
    public function whenExpression(): ?Expression {
        return $this->whenExpression;
    }

    /**
     * @return Expression
     */
    public function thenExpression(): Expression {
        return $this->thenExpression;
    }
}
