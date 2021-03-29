<?php declare(strict_types=1);

/*
 * This file is part of the VV package.
 *
 * (c) Volodymyr Sarnytskyi <v00v4n@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
namespace VV\Db\Driver\QueryStringifiers;

use VV\Db\Sql;

/**
 * Class Expr
 *
 * @package VV\Db\Driver\Sql\Stringifier
 */
class ExpressoinStringifier {

    private QueryStringifier $queryStringifier;

    /**
     * Expr constructor.
     *
     * @param QueryStringifier $queryStringifier
     */
    public function __construct(QueryStringifier $queryStringifier) {
        $this->queryStringifier = $queryStringifier;
    }

    /**
     * @return QueryStringifier
     */
    public function queryStringifier() {
        return $this->queryStringifier;
    }

    public function strExpr(\VV\Db\Sql\Expression $expr, &$params, $withAlias = false) {
        switch (true) {
            case $expr instanceof Sql\SelectQuery:
                $str = $this->strSelectQuery($expr, $params);
                break;
            case $expr instanceof Sql\Plain:
                $str = $this->strPlainSql($expr, $params);
                break;
            case $expr instanceof Sql\DbObject:
                $str = $this->strSqlObj($expr, $params);
                break;
            case $expr instanceof Sql\Param:
                $str = $this->strParam($expr, $params);
                break;
            case $expr instanceof Sql\CaseExpression:
                $str = $this->strCaseExpr($expr, $params);
                break;
            default:
                throw new \InvalidArgumentException('Wrong expression type');
        }

        if ($withAlias && $a = $expr->alias()) $str .= " `$a`";

        return $str;
    }

    public function strSelectQuery(Sql\SelectQuery $select, &$params) {
        $str = $this->queryStringifier()
            ->factory()
            ->createSelectStringifier($select)
            ->stringifyRaw($params);

        return "($str)";
    }

    public function strPlainSql(Sql\Plain $plain, &$params) {
        ($p = $plain->params()) && array_push($params, ...$p);

        return $plain->sql();
    }

    public function strSqlObj(Sql\DbObject $obj, &$params) {
        $path = $obj->path();

        $parts = [];
        foreach ($path as $p) {
            $parts[] = $p == '*' ? $p : "`$p`";
        }

        return implode('.', $parts);
    }

    /**
     * @param mixed|Sql\Param $param
     * @param array           $params
     *
     * @return string
     */
    public function strParam($param, &$params) {
        if ($param instanceof Sql\Param) {
            $param = $param->param();
        } // pam fuiiiww

        $params[] = $param;

        return '?';
    }

    public function strCaseExpr(Sql\CaseExpression $caseExpr, &$params) {
        $str = 'CASE ';

        if ($mainExpr = $caseExpr->mainExpr()) {
            $mainStr = $this->strExpr($mainExpr, $params);
            $str .= "$mainStr ";

            foreach ($caseExpr->thenItems() as $item) {
                $whenstr = $this->strExpr($item->comparisonExpr(), $params);
                $thenstr = $this->strExpr($item->returnExpr(), $params);
                $str .= "WHEN $whenstr THEN $thenstr ";
            }
        } else {
            $condStringifier = $this->queryStringifier()->conditionStringifier();
            foreach ($caseExpr->thenItems() as $item) {
                $whenstr = $condStringifier->buildConditionSql($item->condition())->embed($params);
                $thenstr = $this->strExpr($item->returnExpr(), $params);
                $str .= "WHEN $whenstr THEN $thenstr ";
            }
        }

        if ($else = $caseExpr->elseExpr()) {
            $str .= "ELSE {$this->strExpr($else, $params)} ";
        }

        return "({$str}END)";
    }
}
