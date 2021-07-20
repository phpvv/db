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
 * Class ComparePredicate
 *
 * @package VV\Db\Sql\Predicates
 */
class ComparePredicate extends PredicateBase
{
    public const OP_EQ = '=',
        OP_NE = '<>',
        OP_NE_ALT = '!=',
        OP_LT = '<',
        OP_LTE = '<=',
        OP_GT = '>',
        OP_GTE = '>=';

    private Expression $leftExpression;
    private Expression $rightExpression;
    private string $operator = self::OP_EQ;

    /**
     * ComparePredicate constructor.
     *
     * @param Expression  $left
     * @param Expression  $right
     * @param string|null $operator
     * @param bool        $not
     */
    public function __construct(Expression $left, Expression $right, string $operator = null, bool $not = false)
    {
        parent::__construct($not);

        $this->leftExpression = $left;
        $this->rightExpression = $right;

        if ($operator && $operator = trim($operator)) {
            switch ($operator) {
                case self::OP_NE_ALT:
                    $operator = self::OP_NE;
                    break;
                case self::OP_EQ:
                case self::OP_NE:
                case self::OP_LT:
                case self::OP_LTE:
                case self::OP_GT:
                case self::OP_GTE:
                    break;
                default:
                    throw new \InvalidArgumentException('Wrong compare predicate operator');
            }

            $this->operator = $operator;
        }
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
     * @return string
     */
    public function getOperator(): string
    {
        return $this->operator;
    }
}
