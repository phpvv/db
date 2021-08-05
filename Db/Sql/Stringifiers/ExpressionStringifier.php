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

namespace VV\Db\Sql\Stringifiers;

use VV\Db\Sql\Expressions\CaseExpression;
use VV\Db\Sql\Expressions\DbObject;
use VV\Db\Sql\Expressions\Expression;
use VV\Db\Sql\Expressions\PlainSql;
use VV\Db\Sql\Expressions\SqlParam;
use VV\Db\Sql\SelectQuery;

/**
 * Class Expr
 *
 * @package VV\Db\Driver\Sql\Stringifier
 */
class ExpressionStringifier
{
    private QueryStringifier $queryStringifier;

    /**
     * Expr constructor.
     *
     * @param QueryStringifier $queryStringifier
     */
    public function __construct(QueryStringifier $queryStringifier)
    {
        $this->queryStringifier = $queryStringifier;
    }

    /**
     * @return QueryStringifier
     */
    public function getQueryStringifier(): QueryStringifier
    {
        return $this->queryStringifier;
    }

    /**
     * @param Expression $expression
     * @param array|null $params
     * @param bool       $withAlias
     *
     * @return string
     */
    public function stringifyExpression(Expression $expression, ?array &$params, bool $withAlias = false): string
    {
        $str = match (true) {
            $expression instanceof SelectQuery => $this->stringifySelectQuery($expression, $params),
            $expression instanceof PlainSql => $this->stringifyPlainSql($expression, $params),
            $expression instanceof DbObject => $this->stringifyDbObject($expression, $params),
            $expression instanceof SqlParam => $this->stringifyParam($expression, $params),
            $expression instanceof CaseExpression => $this->stringifyCaseExpression($expression, $params),
            default => throw new \InvalidArgumentException('Wrong expression type'),
        };

        if ($withAlias && $a = $expression->getAlias()) {
            $str .= " `$a`";
        }

        return $str;
    }

    /**
     * @param SelectQuery $select
     * @param array|null  $params
     *
     * @return string
     */
    public function stringifySelectQuery(SelectQuery $select, ?array &$params): string
    {
        $str = $this->getQueryStringifier()
            ->getFactory()
            ->createSelectStringifier($select)
            ->stringifyRaw($params);

        return "($str)";
    }

    /**
     * @param PlainSql   $plain
     * @param array|null $params
     *
     * @return string
     */
    public function stringifyPlainSql(PlainSql $plain, ?array &$params): string
    {
        ($p = $plain->getParams()) && array_push($params, ...$p);

        return $plain->getSql();
    }

    /**
     * @param DbObject   $obj
     * @param array|null $params
     *
     * @return string
     */
    public function stringifyDbObject(DbObject $obj, ?array &$params): string
    {
        $path = $obj->getPath();

        $parts = [];
        foreach ($path as $p) {
            $parts[] = $p == '*' ? $p : "`$p`";
        }

        return implode('.', $parts);
    }

    /**
     * @param mixed|SqlParam $param
     * @param array|null     $params
     *
     * @return string
     */
    public function stringifyParam(mixed $param, ?array &$params): string
    {
        if ($param instanceof SqlParam) {
            $param = $param->getParam();
        } // pam fuiiiww

        $params[] = $param;

        return '?';
    }

    /**
     * @param CaseExpression $caseExpr
     * @param array|null     $params
     *
     * @return string
     */
    public function stringifyCaseExpression(CaseExpression $caseExpr, ?array &$params): string
    {
        $str = 'CASE ';

        if ($mainExpr = $caseExpr->getMainExpression()) {
            $mainStr = $this->stringifyExpression($mainExpr, $params);
            $str .= "$mainStr ";

            foreach ($caseExpr->getThenItems() as $item) {
                $whenStr = $this->stringifyExpression($item->getWhenExpression(), $params);
                $thenStr = $this->stringifyExpression($item->getThenExpression(), $params);
                $str .= "WHEN $whenStr THEN $thenStr ";
            }
        } else {
            $condStringifier = $this->getQueryStringifier()->getConditionStringifier();
            foreach ($caseExpr->getThenItems() as $item) {
                $whenStr = $condStringifier->buildConditionSql($item->getWhenCondition())->embed($params);
                $thenStr = $this->stringifyExpression($item->getThenExpression(), $params);
                $str .= "WHEN $whenStr THEN $thenStr ";
            }
        }

        if ($else = $caseExpr->getElseExpression()) {
            $str .= "ELSE {$this->stringifyExpression($else, $params)} ";
        }

        return "({$str}END)";
    }
}
