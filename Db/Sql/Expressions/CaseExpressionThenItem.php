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
 * Class CaseExpressionThenItem
 *
 * @package VV\Db\Sql\Expressions
 */
class CaseExpressionThenItem
{
    private ?Condition $whenCondition;
    private ?Expression $whenExpression;
    private Expression $thenExpression;

    /**
     * CaseExpressionThenItem constructor.
     *
     * @param Condition|null  $whenCondition
     * @param Expression|null $whenExpression
     * @param Expression      $thenExpression
     */
    public function __construct(?Condition $whenCondition, ?Expression $whenExpression, Expression $thenExpression)
    {
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
    public function getWhenCondition(): ?Condition
    {
        return $this->whenCondition;
    }

    /**
     * @return Expression|null
     */
    public function getWhenExpression(): ?Expression
    {
        return $this->whenExpression;
    }

    /**
     * @return Expression
     */
    public function getThenExpression(): Expression
    {
        return $this->thenExpression;
    }
}
