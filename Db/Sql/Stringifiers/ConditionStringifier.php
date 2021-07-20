<?php declare(strict_types=1);

/*
 * This file is part of the VV package.
 *
 * (c) Volodymyr Sarnytskyi <v00v4n@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
namespace VV\Db\Sql\Stringifiers;

use VV\Db\Sql;
use VV\Db\Sql\Predicates\Predicate;
use VV\Db\Sql\Stringifiers\PlainSql as PlainSql;

/**
 * Class Condition
 *
 * @package VV\Db\Driver\Sql\Stringifier
 */
class ConditionStringifier
{

    /**
     * @var QueryStringifier
     */
    private $queryStringifier;

    /**
     * Condition constructor.
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
    public function queryStringifier()
    {
        return $this->queryStringifier;
    }

    /**
     * @param \VV\Db\Sql\Expressions\Expression $expr
     * @param array                             $params
     * @param bool                              $prnss4plain
     *
     * @return mixed
     */
    public function strColumn(Sql\Expressions\Expression $expr, &$params, $prnss4plain = false)
    {
        $str = $this->queryStringifier()->strColumn($expr, $params);
        if ($prnss4plain) {
            $str = $this->str2prnss4plainSql($str, $expr);
        }

        return $str;
    }

    /**
     * @param \VV\Db\Sql\Condition $condition
     *
     * @return PlainSql
     */
    public function buildConditionSql(Sql\Condition $condition)
    {
        $sql = '';
        $params = [];
        foreach ($condition->getItems() as $item) {
            $predicStr = $this->strPredicate($item->getPredicate(), $params);
            if (!$predicStr) {
                continue;
            }

            if ($sql) {
                $sql .= ' ' . $item->getConnector() . ' ';
            }
            $sql .= $predicStr;
        }
        if ($condition->isNegative()) {
            $sql = "NOT ($sql)";
        }

        return $this->createPlainSql($sql, $params);
    }

    public function strComparePredicate(Sql\Predicates\ComparePredicate $compare, &$params)
    {
        $leftExpr = $compare->getLeftExpression();
        $rightExpr = $compare->getRightExpression();
        $this->decorateParamsByModel($leftExpr, $rightExpr);

        $lstr = $this->strColumn($leftExpr, $params);
        $rstr = $this->strColumn($rightExpr, $params, true);

        $str = "$lstr {$compare->getOperator()} $rstr";
        if ($compare->isNegative()) {
            return "NOT ($str)";
        }

        return $this->str2prnss4plainSql($str, $leftExpr);
    }

    public function strLikePredicate(Sql\Predicates\LikePredicate $like, &$params)
    {
        $lstr = $this->strColumn($leftExpr = $like->getLeftExpression(), $params);
        $rstr = $this->strColumn($like->getRightExpression(), $params, true);
        $notstr = $like->isNegative() ? 'NOT ' : '';

        $str = $this->strPreparedLike($lstr, $rstr, $notstr, $like->isCaseInsensitive());

        return $this->str2prnss4plainSql($str, $leftExpr);
    }

    public function strBetweenPredicate(Sql\Predicates\BetweenPredicate $between, &$params)
    {
        $expr = $between->getExpression();
        $from = $between->getFromExpression();
        $till = $between->getTillExpression();

        $this->decorateParamsByModel($expr, $from, $till);

        $exprstr = $this->strColumn($expr, $params);
        $fromstr = $this->strColumn($from, $params, true);
        $tillstr = $this->strColumn($till, $params, true);

        $not = $between->isNegative() ? 'NOT ' : '';

        $str = "$exprstr {$not}BETWEEN $fromstr AND $tillstr";

        return $this->str2prnss4plainSql($str, $expr);
    }

    public function strInPredicate(Sql\Predicates\InPredicate $in, &$params)
    {
        $exprstr = $this->strColumn($expr = $in->getExpression(), $params);

        $inParams = $in->getParams();
        $this->decorateParamsByModel($in->getExpression(), ...$inParams);

        // build values clause: (?, ?, SOME_FUNC(field))
        $valsarr = [];
        foreach ($inParams as $p) {
            if ($p instanceof Sql\Expressions\Expression) {
                $valsarr[] = $this->strColumn($p, $params);
            } else {
                $valsarr[] = $this->queryStringifier()->strParam($p, $params);
            }
        }
        $vals = implode(', ', $valsarr);

        $not = $in->isNegative() ? 'NOT ' : '';
        $str = "$exprstr {$not}IN ($vals)";

        return $this->str2prnss4plainSql($str, $expr);
    }

    public function strIsNullPredicate(Sql\Predicates\IsNullPredicate $isNull, &$params)
    {
        $exprstr = $this->strColumn($expr = $isNull->getExpression(), $params);
        $not = $isNull->isNegative() ? ' NOT' : '';
        $str = "$exprstr IS{$not} NULL";

        return $this->str2prnss4plainSql($str, $expr);
    }

    public function strCustomPredicate(Sql\Predicates\CustomPredicate $custom, &$params)
    {
        $str = $this->strColumn($custom->getExpression(), $params, true);
        ($p = $custom->getParams()) && array_push($params, ...$p);

        if ($custom->isNegative()) {
            return "NOT $str";
        }

        return $str;
    }

    public function strExistsPredicate(Sql\Predicates\ExistsPredicate $exists, &$params)
    {
        $selectStr = $this->queryStringifier()->factory()
            ->createSelectStringifier($exists->getQuery())
            ->stringifyRaw($params);

        $str = "EXISTS ($selectStr)";

        if ($exists->isNegative()) {
            return "NOT $str";
        }

        return $str;
    }

    protected function createPlainSql($sql, array $params = [])
    {
        return new PlainSql($sql, $params);
    }

    protected function strPreparedLike(string $lstr, string $rstr, string $notstr, bool $caseInsensitive)
    {
        if ($caseInsensitive) {
            return "LOWER($lstr) {$notstr}LIKE LOWER($rstr)";
        }

        return "$lstr {$notstr}LIKE $rstr";
    }

    /**
     * @param Predicate $predicate
     * @param           $params
     *
     * @return string|null
     */
    private function strPredicate(Predicate $predicate, &$params): ?string
    {
        switch (true) {
            case $predicate instanceof Sql\Condition:
                if ($predicate->isEmpty()) {
                    return null;
                }

                return "({$this->buildConditionSql($predicate)->embed($params)})";

            case $predicate instanceof Sql\Predicates\ComparePredicate:
                return $this->strComparePredicate($predicate, $params);

            case $predicate instanceof Sql\Predicates\LikePredicate:
                return $this->strLikePredicate($predicate, $params);

            case $predicate instanceof Sql\Predicates\BetweenPredicate:
                return $this->strBetweenPredicate($predicate, $params);

            case $predicate instanceof Sql\Predicates\InPredicate:
                return $this->strInPredicate($predicate, $params);

            case $predicate instanceof Sql\Predicates\IsNullPredicate:
                return $this->strIsNullPredicate($predicate, $params);

            case $predicate instanceof Sql\Predicates\CustomPredicate:
                return $this->strCustomPredicate($predicate, $params);

            case $predicate instanceof Sql\Predicates\ExistsPredicate:
                return $this->strExistsPredicate($predicate, $params);

            default:
                throw new \InvalidArgumentException('Unknown predicate');
        }
    }

    private function decorateParamsByModel($field, &...$params)
    {
        if ($field instanceof Sql\Expressions\DbObject) {
            foreach ($params as &$p) {
                if ($p instanceof Sql\Expressions\SqlParam) {
                    $val = $p->getParam();
                } else {
                    $val = $p;
                }

                $val = $this->queryStringifier()->decorateParamForCond($val, $field);

                if ($p instanceof Sql\Expressions\SqlParam) {
                    $p->setParam($val);
                } else {
                    $p = $val;
                }
            }
            unset($p);
        }

        return $this;
    }

    /**
     * @param string                            $str
     * @param \VV\Db\Sql\Expressions\Expression $expr
     *
     * @return string
     */
    private function str2prnss4plainSql(string $str, Sql\Expressions\Expression $expr): string
    {
        if ($expr instanceof Sql\Expressions\PlainSql) {
            return "($str)";
        }

        return $str;
    }
}
