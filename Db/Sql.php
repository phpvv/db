<?php declare(strict_types=1);

/*
 * This file is part of the VV package.
 *
 * (c) Volodymyr Sarnytskyi <v00v4n@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
namespace VV\Db;

use JetBrains\PhpStorm\Pure;
use VV\Db\Sql\Condition;
use VV\Db\Sql\Condition\Predicate;
use VV\Db\Sql\Expressions\Expression;

/**
 * Static class with factory methods
 *
 * @package VV\Db
 */
final class Sql {

    /**
     * @param string|int|\VV\Db\Sql\Expressions\Expression $expression
     * @param array                                        $params
     *
     * @return Expression
     */
    public static function expression(string|int|Expression $expression, array $params = []): Expression {
        if (is_object($expression)) {
            if (!$expression instanceof Expression) {
                throw new \InvalidArgumentException('Wrong object type');
            }

            return $expression;
        }

        if ($o = Sql\Expressions\DbObject::create($expression)) return $o;

        return self::plain((string)$expression, $params);
    }

    /**
     * @param mixed $param
     *
     * @return \VV\Db\Sql\Expressions\Expression
     */
    public static function param(mixed $param): Expression {
        if ($param instanceof Expression) return $param;

        return new Sql\Expressions\SqlParam($param);
    }

    /**
     * @param string|int $sql
     * @param array  $params
     *
     * @return \VV\Db\Sql\Expressions\PlainSql
     */
    #[Pure]
    public static function plain(string|int $sql, array $params = []): Sql\Expressions\PlainSql {
        return new Sql\Expressions\PlainSql($sql, $params);
    }

    /**
     * @param Condition|Predicate|array|string|null $condition
     *
     * @return Condition
     */
    public static function condition($condition = null): Condition {
        if (!$condition) {
            return new Condition;
        }

        if ($condition instanceof Condition) {
            return $condition;
        }

        if ($condition instanceof Predicate) {
            return (new Condition)->addPredicItem($condition);
        }

        if (is_array($condition)) {
            return (new Condition)->and($condition);
        }

        if (is_string($condition)) {
            return (new Condition)->expr($condition);
        }

        throw new \InvalidArgumentException('Wrong argument for condition');
    }

    /**
     * @param string|\VV\Db\Sql\Expressions\Expression|null $case
     * @param mixed|null                                    $when
     * @param mixed|null                                    $then
     * @param mixed|null                                    $else
     *
     * @return \VV\Db\Sql\Expressions\CaseExpression
     */
    public static function case($case = null, $when = null, $then = null, $else = null): Sql\Expressions\CaseExpression {
        $caseExpr = new Sql\Expressions\CaseExpression($case);
        if ($when) $caseExpr->when($when)->then($then);
        if ($else) $caseExpr->else($else);

        return $caseExpr;
    }
}
