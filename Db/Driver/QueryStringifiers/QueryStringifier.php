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

use VV\Db\Driver\Driver;
use VV\Db\Driver\QueryStringifiers\PlainSql as PlainSql;
use VV\Db\Sql;


/**
 * Class QueryStringifier
 *
 * @package VV\Db\Driver\Sql\Stringifier
 */
abstract class QueryStringifier {

    private Factory $factory;
    private array $params = [];

    /**
     * @var ExpressoinStringifier
     */
    private $exprStringifier;

    /**
     * @var ConditionStringifier
     */
    private $conditionStringifier;

    /**
     * Stringifier constructor.
     *
     * @param Driver $driver
     */
    public function __construct(Factory $driver) {
        $this->factory = $driver;
    }


    /**
     * Builds SQL-string and returns by reference it parameters in proper order
     *
     * @param array &$params (out)
     *
     * @return string
     */
    final public function stringify(&$params) {
        return $this->stringifyFinalDecorate($this->stringifyRaw($params));
    }

    /**
     * Returns same as {@link stringify()} without returning parameters
     *
     * @return mixed
     */
    final public function toString() {
        return $this->stringifyRaw($params);
    }

    final public function __toString() {
        return $this->toString();
    }

    /**
     * @return array
     */
    final public function params() {
        return $this->params;
    }

    final public function appendParams(...$params) {
        $params && array_push($this->params, ...$params);

        return $this;
    }

    /**
     * @return Factory
     */
    public function factory(): Factory {
        return $this->factory;
    }

    /**
     * @return ExpressoinStringifier
     */
    public function exprStringifier(): ExpressoinStringifier {
        if (!$this->exprStringifier) {
            $this->exprStringifier = $this->createExprStringifier();
        }

        return $this->exprStringifier;
    }

    /**
     * @return ConditionStringifier
     */
    public function conditionStringifier() {
        if (!$this->conditionStringifier) {
            $this->conditionStringifier = $this->createConditionStringifier();
        }

        return $this->conditionStringifier;
    }

    public function decorateParamForCond($param, $field) {
        if ($param instanceof \VV\Db\Param) {
            return $param->setValue($this->decorateParamForCond($param->value(), $field));
        }

        return ($o = $this->fieldModel($field, $this->queryTableClause()))
            ? $o->prepeareValueForCondition($param)
            : $param;
    }

    /**
     * @param \VV\Db\Sql\Expression $expr
     * @param array                 $params
     * @param bool                  $withAlias
     *
     * @return mixed
     */
    public function strExpr(\VV\Db\Sql\Expression $expr, &$params, $withAlias = false) {
        return $this->exprStringifier()->strExpr($expr, $params, $withAlias);
    }

    /**
     * @param mixed|Sql\Param $param
     * @param array           $params
     *
     * @return string
     */
    public function strParam($param, &$params) {
        return $this->exprStringifier()->strParam($param, $params);
    }

    /**
     * @inheritdoc
     */
    public function strColumn(\VV\Db\Sql\Expression $expr, &$params, $withAlias = false) {
        if ($expr instanceof Sql\DbObject) {
            if (!$expr->owner()) { // obj without owner (without table alias)
                $tableClause = $this->queryTableClause();
                if (count($tableClause->items()) > 1) {
                    $expr->setOwner($tableClause->mainTableAlias());
                }
            }
        }

        return $this->strExpr($expr, $params, $withAlias);
    }

    /**
     * Returns supported by this stringifier ids of clauses
     *
     * @return int
     */
    abstract public function supportedClausesIds();

    abstract public function stringifyRaw(&$params);

    /**
     * @return Sql\Clauses\Table
     */
    abstract public function queryTableClause();

    /**
     * @param                  $field
     * @param Sql\Clauses\Table $tableClause
     *
     * @return \VV\Db\Model\Field|null
     */
    protected function fieldModel($field, Sql\Clauses\Table $tableClause) {
        if (!$field) return null;

        if ($field instanceof \VV\Db\Model\Field) {
            return $field;
        }

        if (!$field instanceof Sql\DbObject) {
            $field = Sql\DbObject::create($field);
            if (!$field) return null;
        }

        $owner = $field->owner();
        $tblMdl = $tableClause->tableModelOrMain($owner ? $owner->name() : null);
        if ($tblMdl) return $tblMdl->fields()->get($field->name());

        return null;
    }

    /**
     * @param Sql\Expression[] $exprList
     * @param array            $params
     * @param bool             $withAlias
     *
     * @return string
     */
    protected function strColumnList(array $exprList, &$params, $withAlias = false) {
        $strarr = [];

        foreach ($exprList as $expr) {
            $strarr[] = $this->strColumn($expr, $params, $withAlias);
        }

        return implode(', ', $strarr);
    }

    protected function buildConditionSql(Sql\Condition $condition) {
        return $this->conditionStringifier()->buildConditionSql($condition);
    }

    protected function strWhereClause(Sql\Condition $where, &$params) {
        if ($where->isEmpty()) return '';

        return ' WHERE ' . $this->buildConditionSql($where)->embed($params);
    }

    /**
     * @param Sql\Clauses\Table $table
     *
     * @return PlainSql
     */
    protected function buildTableSql(Sql\Clauses\Table $table) {
        $condstr = $this->conditionStringifier();

        $sql = '';
        $params = [];
        $useAlais = $this->useAliasForTable($table);
        foreach ($table->items() as $item) {
            $tblNameStr = $this->strExpr($item->table(), $params, $useAlais);

            // todo: reconsider (move to method or something else)
            if ($useidx = $item->useIndex()) {
                $tblNameStr .= ' USE INDEX (' . (is_array($useidx) ? implode(', ', $useidx) : $useidx) . ') ';
            }

            if ($sql) {
                $on = $condstr->buildConditionSql($item->joinCondition());

                $sql .= " {$item->joinType()} $tblNameStr ON ({$on->embed($params)})";
            } else {
                $sql = $tblNameStr;
            }
        }

        return $this->createPlainSql($sql, $params);
    }

    protected function createPlainSql($sql, array $params = []) {
        return new PlainSql($sql, $params);
    }

    /**
     * @return ExpressoinStringifier
     */
    protected function createExprStringifier() {
        return new ExpressoinStringifier($this);
    }

    protected function createConditionStringifier() {
        return new ConditionStringifier($this);
    }

    protected function stringifyFinalDecorate($sql) {
        return $sql;
    }

    protected function useAliasForTable(Sql\Clauses\Table $table) {
        return true;
    }
}
