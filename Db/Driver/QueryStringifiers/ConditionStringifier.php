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

use VV\Db\Driver\QueryStringifiers\PlainSql as PlainSql;
use VV\Db\Sql;

/**
 * Class Condition
 *
 * @package VV\Db\Driver\Sql\Stringifier
 */
class ConditionStringifier {

    /**
     * @var QueryStringifier
     */
    private $queryStringifier;

    /**
     * Condition constructor.
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

    /**
     * @param \VV\Db\Sql\Expression $expr
     * @param  array                $params
     * @param bool                  $prnss4plain
     *
     * @return mixed
     */
    public function strColumn(\VV\Db\Sql\Expression $expr, &$params, $prnss4plain = false) {
        $str = $this->queryStringifier()->strColumn($expr, $params);
        if ($prnss4plain) $str = $this->str2prnss4plainSql($str, $expr);

        return $str;
    }

    /**
     * @param \VV\Db\Sql\Condition $condition
     *
     * @return PlainSql
     */
    public function buildConditionSql(Sql\Condition $condition) {
        $sql = '';
        $params = [];
        foreach ($condition->items() as $item) {
            $predicStr = $this->strPredic($item->predicate(), $params);
            if (!$predicStr) continue;

            if ($sql) $sql .= ' ' . $item->connector() . ' ';
            $sql .= $predicStr;
        }
        if ($condition->isNegative()) $sql = "NOT ($sql)";

        return $this->createPlainSql($sql, $params);
    }

    public function strComparePredicate(Sql\Condition\Predicates\Compare $compare, &$params) {
        $leftExpr = $compare->leftExpr();
        $rightExpr = $compare->rightExpr();
        $this->decorateParamsByModel($leftExpr, $rightExpr);

        $lstr = $this->strColumn($leftExpr, $params);
        $rstr = $this->strColumn($rightExpr, $params, true);

        $str = "$lstr {$compare->operator()} $rstr";
        if ($compare->isNegative()) return "NOT ($str)";

        return $this->str2prnss4plainSql($str, $leftExpr);
    }

    public function strLikePredicate(Sql\Condition\Predicates\Like $like, &$params) {
        $lstr = $this->strColumn($leftExpr = $like->leftExpr(), $params);
        $rstr = $this->strColumn($like->rightExpr(), $params, true);
        $notstr = $like->isNegative() ? 'NOT ' : '';

        $str = $this->strPreparedLike($lstr, $rstr, $notstr, $like->isCaseInsensitive());

        return $this->str2prnss4plainSql($str, $leftExpr);
    }

    public function strBetweenPredicate(Sql\Condition\Predicates\Between $between, &$params) {
        $expr = $between->expr();
        $from = $between->from();
        $till = $between->till();

        $this->decorateParamsByModel($expr, $from, $till);

        $exprstr = $this->strColumn($expr, $params);
        $fromstr = $this->strColumn($from, $params, true);
        $tillstr = $this->strColumn($till, $params, true);

        $not = $between->isNegative() ? 'NOT ' : '';

        $str = "$exprstr {$not}BETWEEN $fromstr AND $tillstr";

        return $this->str2prnss4plainSql($str, $expr);
    }

    public function strInPredicate(Sql\Condition\Predicates\In $in, &$params) {
        $exprstr = $this->strColumn($expr = $in->expr(), $params);

        $inParams = $in->params();
        $this->decorateParamsByModel($in->expr(), ...$inParams);

        // build values clause: (?, ?, SOME_FUNC(field))
        $valsarr = [];
        foreach ($inParams as $p) {
            if ($p instanceof Sql\Expression) {
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

    public function strIsNullPredicate(Sql\Condition\Predicates\IsNull $isNull, &$params) {
        $exprstr = $this->strColumn($expr = $isNull->expr(), $params);
        $not = $isNull->isNegative() ? ' NOT' : '';
        $str = "$exprstr IS{$not} NULL";

        return $this->str2prnss4plainSql($str, $expr);
    }

    public function strCustomPredicate(Sql\Condition\Predicates\Custom $custom, &$params) {
        $str = $this->strColumn($custom->expr(), $params, true);
        ($p = $custom->params()) && array_push($params, ...$p);

        if ($custom->isNegative()) return "NOT $str";

        return $str;
    }

    public function strExistsPredicate(Sql\Condition\Predicates\Exists $exists, &$params) {
        $selectStr = $this->queryStringifier()->factory()
            ->createSelectStringifier($exists->query())
            ->stringifyRaw($params);

        $str = "EXISTS ($selectStr)";

        if ($exists->isNegative()) return "NOT $str";

        return $str;
    }

    protected function createPlainSql($sql, array $params = []) {
        return new PlainSql($sql, $params);
    }

    protected function strPreparedLike(string $lstr, string $rstr, string $notstr, bool $caseInsensitive) {
        if ($caseInsensitive) {
            return "LOWER($lstr) {$notstr}LIKE LOWER($rstr)";
        }

        return "$lstr {$notstr}LIKE $rstr";
    }

    /**
     * @param \VV\Db\Sql\Condition\Predicate $predic
     * @param               $params
     *
     * @return string|null
     */
    private function strPredic(Sql\Condition\Predicate $predic, &$params): ?string {
        switch (true) {
            case $predic instanceof Sql\Condition:
                if ($predic->isEmpty()) return null;

                return "({$this->buildConditionSql($predic)->embed($params)})";

            case $predic instanceof Sql\Condition\Predicates\Compare:
                return $this->strComparePredicate($predic, $params);

            case $predic instanceof Sql\Condition\Predicates\Like:
                return $this->strLikePredicate($predic, $params);

            case $predic instanceof Sql\Condition\Predicates\Between:
                return $this->strBetweenPredicate($predic, $params);

            case $predic instanceof Sql\Condition\Predicates\In:
                return $this->strInPredicate($predic, $params);

            case $predic instanceof Sql\Condition\Predicates\IsNull:
                return $this->strIsNullPredicate($predic, $params);

            case $predic instanceof Sql\Condition\Predicates\Custom:
                return $this->strCustomPredicate($predic, $params);

            case $predic instanceof Sql\Condition\Predicates\Exists:
                return $this->strExistsPredicate($predic, $params);

            default:
                throw new \InvalidArgumentException('Unknown predicate');
        }
    }

    private function decorateParamsByModel($field, &...$params) {
        if ($field instanceof Sql\DbObject) {
            foreach ($params as &$p) {
                if ($p instanceof Sql\Param) {
                    $val = $p->param();
                } else $val = $p;

                $val = $this->queryStringifier()->decorateParamForCond($val, $field);

                if ($p instanceof Sql\Param) {
                    $p->setParam($val);
                } else $p = $val;
            }
            unset($p);
        }

        return $this;
    }

    /**
     * @param string         $str
     * @param Sql\Expression $expr
     *
     * @return string
     */
    private function str2prnss4plainSql(string $str, Sql\Expression $expr): string {
        if ($expr instanceof Sql\Plain) return "($str)";

        return $str;
    }
}
