<?php

/*
 * This file is part of the VV package.
 *
 * (c) Volodymyr Sarnytskyi <v00v4n@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace VV\Db;

use VV\Db\Sql\Condition;
use VV\Db\Sql\Expressions\CaseExpression;
use VV\Db\Sql\Expressions\DbObject;
use VV\Db\Sql\Expressions\Expression;
use VV\Db\Sql\Expressions\PlainSql;
use VV\Db\Sql\Expressions\SqlParam;
use VV\Db\Sql\Predicates\Predicate;

/**
 * Static class with factory methods
 *
 * @package VV\Db
 */
final class Sql
{

    public static function expression(
        string|int|Expression $expression,
        array $params = [],
        bool $parseAlias = true
    ): Expression {
        if ($expression instanceof Expression) {
            return $expression;
        }

        if ($o = DbObject::create($expression, null, $parseAlias)) {
            return $o;
        }

        return self::plain((string)$expression, $params);
    }

    public static function param(mixed $param): Expression
    {
        if ($param instanceof Expression) {
            return $param;
        }

        return new SqlParam($param);
    }

    public static function plain(string|int $sql, array $params = []): PlainSql
    {
        return new PlainSql($sql, $params);
    }

    public static function condition(array|string|int|Expression|Predicate $condition = null): Condition
    {
        if ($condition === null) {
            return new Condition();
        }

        if ($condition instanceof Condition) {
            return $condition;
        }

        if ($condition instanceof Predicate) {
            return (new Condition())->addPredicate($condition);
        }

        if (is_array($condition)) {
            return (new Condition())->and($condition);
        }

        return (new Condition())->expression($condition);
    }

    /**
     * @param string|int|Expression|null                 $case
     * @param string|int|Expression|Predicate|array|null $when
     * @param string|int|Expression|null                 $then
     * @param string|int|Expression|null                 $else
     *
     * @return CaseExpression
     */
    public static function case(
        mixed $case = null,
        mixed $when = null,
        mixed $then = null,
        mixed $else = null
    ): CaseExpression {
        $caseExpr = new CaseExpression($case);
        if ($when !== null) {
            $caseExpr->when($when)->then($then);
        }
        if ($else !== null) {
            $caseExpr->else($else);
        }

        return $caseExpr;
    }
}
