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

use VV\Db\Sql\Condition;
use VV\Db\Sql\Expressions\DbObject;
use VV\Db\Sql\Expressions\Expression;
use VV\Db\Sql\Expressions\PlainSql;
use VV\Db\Sql\Expressions\SqlParam;
use VV\Db\Sql\Predicates\BetweenPredicate;
use VV\Db\Sql\Predicates\ComparePredicate;
use VV\Db\Sql\Predicates\CustomPredicate;
use VV\Db\Sql\Predicates\ExistsPredicate;
use VV\Db\Sql\Predicates\InPredicate;
use VV\Db\Sql\Predicates\IsNullPredicate;
use VV\Db\Sql\Predicates\LikePredicate;
use VV\Db\Sql\Predicates\Predicate;

/**
 * Class Condition
 *
 * @package VV\Db\Driver\Sql\Stringifier
 */
class ConditionStringifier
{
    private QueryStringifier $queryStringifier;

    /**
     * ConditionStringifier constructor.
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
     * @param bool       $parentheses4plain
     *
     * @return string
     */
    public function stringifyColumn(Expression $expression, ?array &$params, bool $parentheses4plain = false): string
    {
        $str = $this->getQueryStringifier()->stringifyColumn($expression, $params);
        if ($parentheses4plain) {
            $str = $this->strToParenthesesForPlainSql($str, $expression);
        }

        return $str;
    }

    /**
     * @param Condition $condition
     *
     * @return SqlPart
     */
    public function buildConditionSql(Condition $condition): SqlPart
    {
        $sql = '';
        $params = [];
        foreach ($condition->getItems() as $item) {
            $predicateString = $this->strPredicate($item->getPredicate(), $params);
            if (!$predicateString) {
                continue;
            }

            if ($sql) {
                $sql .= ' ' . $item->getConnector() . ' ';
            }
            $sql .= $predicateString;
        }
        if ($condition->isNegative()) {
            $sql = "NOT ($sql)";
        }

        return $this->createSqlPart($sql, $params);
    }

    /**
     * @param ComparePredicate $compare
     * @param array|null       $params
     *
     * @return string
     */
    public function stringifyComparePredicate(ComparePredicate $compare, ?array &$params): string
    {
        $leftExpression = $compare->getLeftExpression();
        $rightExpression = $compare->getRightExpression();
        $this->decorateParamsByModel($leftExpression, $rightExpression);

        $leftStr = $this->stringifyColumn($leftExpression, $params);
        $rightStr = $this->stringifyColumn($rightExpression, $params, true);

        $str = "$leftStr {$compare->getOperator()} $rightStr";
        if ($compare->isNegative()) {
            return "NOT ($str)";
        }

        return $this->strToParenthesesForPlainSql($str, $leftExpression);
    }

    /**
     * @param LikePredicate $like
     * @param array|null    $params
     *
     * @return string
     */
    public function stringifyLikePredicate(LikePredicate $like, ?array &$params): string
    {
        $leftStr = $this->stringifyColumn($leftExpr = $like->getLeftExpression(), $params);
        $rightStr = $this->stringifyColumn($like->getRightExpression(), $params, true);
        $notStr = $like->isNegative() ? 'NOT ' : '';

        $str = $this->stringifyPreparedLike($leftStr, $rightStr, $notStr, $like->isCaseInsensitive());

        return $this->strToParenthesesForPlainSql($str, $leftExpr);
    }

    /**
     * @param BetweenPredicate $between
     * @param array|null       $params
     *
     * @return string
     */
    public function stringifyBetweenPredicate(BetweenPredicate $between, ?array &$params): string
    {
        $expr = $between->getExpression();
        $from = $between->getFromExpression();
        $till = $between->getTillExpression();

        $this->decorateParamsByModel($expr, $from, $till);

        $expressionStr = $this->stringifyColumn($expr, $params);
        $fromStr = $this->stringifyColumn($from, $params, true);
        $tillStr = $this->stringifyColumn($till, $params, true);

        $not = $between->isNegative() ? 'NOT ' : '';

        $str = "$expressionStr {$not}BETWEEN $fromStr AND $tillStr";

        return $this->strToParenthesesForPlainSql($str, $expr);
    }

    /**
     * @param InPredicate $in
     * @param array|null  $params
     *
     * @return string
     */
    public function stringifyInPredicate(InPredicate $in, ?array &$params): string
    {
        $exprStr = $this->stringifyColumn($expr = $in->getExpression(), $params);

        $inParams = $in->getParams();
        $this->decorateParamsByModel($in->getExpression(), ...$inParams);

        // build values clause: (?, ?, SOME_FUNC(field))
        $valuesArray = [];
        foreach ($inParams as $p) {
            if ($p instanceof Expression) {
                $valuesArray[] = $this->stringifyColumn($p, $params);
            } else {
                $valuesArray[] = $this->getQueryStringifier()->stringifyParam($p, $params);
            }
        }
        $values = implode(', ', $valuesArray);

        $not = $in->isNegative() ? 'NOT ' : '';
        $str = "$exprStr {$not}IN ($values)";

        return $this->strToParenthesesForPlainSql($str, $expr);
    }

    /**
     * @param IsNullPredicate $isNull
     * @param array|null      $params
     *
     * @return string
     */
    public function stringifyIsNullPredicate(IsNullPredicate $isNull, ?array &$params): string
    {
        $expressionStr = $this->stringifyColumn($expr = $isNull->getExpression(), $params);
        $not = $isNull->isNegative() ? ' NOT' : '';
        $str = "$expressionStr IS{$not} NULL";

        return $this->strToParenthesesForPlainSql($str, $expr);
    }

    /**
     * @param CustomPredicate $custom
     * @param array|null      $params
     *
     * @return string
     */
    public function stringifyCustomPredicate(CustomPredicate $custom, ?array &$params): string
    {
        $str = $this->stringifyColumn($custom->getExpression(), $params, true);
        ($p = $custom->getParams()) && array_push($params, ...$p);

        if ($custom->isNegative()) {
            return "NOT $str";
        }

        return $str;
    }

    /**
     * @param ExistsPredicate $exists
     * @param array|null      $params
     *
     * @return string
     */
    public function stringifyExistsPredicate(ExistsPredicate $exists, ?array &$params): string
    {
        $selectStr = $this->getQueryStringifier()->getFactory()
            ->createSelectStringifier($exists->getQuery())
            ->stringifyRaw($params);

        $str = "EXISTS ($selectStr)";

        if ($exists->isNegative()) {
            return "NOT $str";
        }

        return $str;
    }

    /**
     * @param string $sql
     * @param array  $params
     *
     * @return SqlPart
     */
    protected function createSqlPart(string $sql, array $params = []): SqlPart
    {
        return new SqlPart($sql, $params);
    }

    protected function stringifyPreparedLike(
        string $leftStr,
        string $rightStr,
        string $notStr,
        bool $caseInsensitive
    ): string {
        if ($caseInsensitive) {
            return "LOWER($leftStr) {$notStr}LIKE LOWER($rightStr)";
        }

        return "$leftStr {$notStr}LIKE $rightStr";
    }

    /**
     * @param Predicate  $predicate
     * @param array|null $params
     *
     * @return string|null
     */
    private function strPredicate(Predicate $predicate, ?array &$params): ?string
    {
        switch (true) {
            case $predicate instanceof Condition:
                if ($predicate->isEmpty()) {
                    return null;
                }

                return "({$this->buildConditionSql($predicate)->embed($params)})";

            case $predicate instanceof ComparePredicate:
                return $this->stringifyComparePredicate($predicate, $params);

            case $predicate instanceof LikePredicate:
                return $this->stringifyLikePredicate($predicate, $params);

            case $predicate instanceof BetweenPredicate:
                return $this->stringifyBetweenPredicate($predicate, $params);

            case $predicate instanceof InPredicate:
                return $this->stringifyInPredicate($predicate, $params);

            case $predicate instanceof IsNullPredicate:
                return $this->stringifyIsNullPredicate($predicate, $params);

            case $predicate instanceof CustomPredicate:
                return $this->stringifyCustomPredicate($predicate, $params);

            case $predicate instanceof ExistsPredicate:
                return $this->stringifyExistsPredicate($predicate, $params);

            default:
                throw new \InvalidArgumentException('Unknown predicate');
        }
    }

    private function decorateParamsByModel($field, &...$params): void
    {
        if ($field instanceof DbObject) {
            foreach ($params as &$p) {
                if ($p instanceof SqlParam) {
                    $val = $p->getParam();
                } else {
                    $val = $p;
                }

                $val = $this->getQueryStringifier()->decorateParamForCondition($val, $field);

                if ($p instanceof SqlParam) {
                    $p->setParam($val);
                } else {
                    $p = $val;
                }
            }
            unset($p);
        }
    }

    /**
     * @param string     $str
     * @param Expression $expression
     *
     * @return string
     */
    private function strToParenthesesForPlainSql(string $str, Expression $expression): string
    {
        if ($expression instanceof PlainSql) {
            return "($str)";
        }

        return $str;
    }
}
