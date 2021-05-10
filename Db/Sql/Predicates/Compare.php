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
 * Class Compare
 *
 * @package VV\Db\Sql\Predicate
 */
class Compare extends Base {

    const OP_EQ = '=',
        OP_NE = '<>',
        OP_NE_ALT = '!=',
        OP_LT = '<',
        OP_LTE = '<=',
        OP_GT = '>',
        OP_GTE = '>=';

    /**
     * @var \VV\Db\Sql\Expressions\Expression
     */
    private $leftExpr;

    /**
     * @var Expression
     */
    private $rightExpr;

    /**
     * @var string
     */
    private $operator = self::OP_EQ;

    /**
     * Compare constructor.
     *
     * @param Expression                        $leftExpr
     * @param \VV\Db\Sql\Expressions\Expression $rightExpr
     * @param string                            $operator
     * @param bool                              $not
     */
    public function __construct(Expression $leftExpr, Expression $rightExpr, string $operator = null, bool $not = false) {
        $this->leftExpr = $leftExpr;
        $this->rightExpr = $rightExpr;
        $this->not = $not;

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
     * @return \VV\Db\Sql\Expressions\Expression
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
     * @return string
     */
    public function operator(): string {
        return $this->operator;
    }
}
