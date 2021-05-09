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
use VV\Db\Sql\Condition\{
    Condition,
    Predicate,
};
use VV\Db\Sql\Expressions\{
    CaseExpression,
    DbObject,
    Expression,
    PlainSql,
    SqlParam,
};

/**
 * Static class with factory methods
 *
 * @package VV\Db
 */
final class Sql {

    /**
     * @param string|int|Expression $expression
     * @param array                 $params
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

        if ($o = DbObject::create($expression)) return $o;

        return self::plain((string)$expression, $params);
    }

    /**
     * @param mixed $param
     *
     * @return Expression
     */
    public static function param(mixed $param): Expression {
        if ($param instanceof Expression) return $param;

        return new SqlParam($param);
    }

    /**
     * @param string|int $sql
     * @param array      $params
     *
     * @return PlainSql
     */
    #[Pure]
    public static function plain(string|int $sql, array $params = []): PlainSql {
        return new PlainSql($sql, $params);
    }

    /**
     * @param string|int|array|Expression|Predicate|null $condition
     *
     * @return Condition
     */
    public static function condition(array|string|int|Expression|Predicate $condition = null): Condition {
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

        return (new Condition)->expr($condition);
    }

    /**
     * @param string|int|Expression|null                 $case
     * @param string|int|Expression|Predicate|array|null $when
     * @param string|int|Expression|null                 $then
     * @param string|int|Expression|null                 $else
     *
     * @return CaseExpression
     */
    public static function case(mixed $case = null, mixed $when = null, mixed $then = null, mixed $else = null): CaseExpression {
        $caseExpr = new CaseExpression($case);
        if ($when !== null) $caseExpr->when($when)->then($then);
        if ($else !== null) $caseExpr->else($else);

        return $caseExpr;
    }
}
