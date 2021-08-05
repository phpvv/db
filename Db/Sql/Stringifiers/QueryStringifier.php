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

use VV\Db\Model\Field;
use VV\Db\Sql\Clauses\TableClause;
use VV\Db\Sql\Condition;
use VV\Db\Sql\Expressions\DbObject;
use VV\Db\Sql\Expressions\Expression;
use VV\Db\Sql\Expressions\SqlParam;

/**
 * Class QueryStringifier
 *
 * @package VV\Db\Driver\Sql\Stringifier
 */
abstract class QueryStringifier
{
    private Factory $factory;
    private array $params = [];
    private ?ExpressionStringifier $expressionStringifier = null;
    private ?ConditionStringifier $conditionStringifier = null;

    /**
     * Stringifier constructor.
     *
     * @param Factory $factory
     */
    public function __construct(Factory $factory)
    {
        $this->factory = $factory;
    }

    /**
     * Builds SQL-string and returns by reference it parameters in proper order
     *
     * @param array|null &$params (out)
     *
     * @return string
     */
    final public function stringify(?array &$params): string
    {
        return $this->stringifyFinalDecorate($this->stringifyRaw($params));
    }

    /**
     * Returns same as {@link stringify()} without returning parameters and final decoration
     *
     * @return mixed
     */
    final public function toString(): string
    {
        return $this->stringifyRaw($params);
    }

    /**
     * @return array
     */
    final public function getParams(): array
    {
        return $this->params;
    }

    /**
     * @param ...$params
     *
     * @return $this
     */
    final public function appendParams(...$params): static
    {
        $params && array_push($this->params, ...$params);

        return $this;
    }

    /**
     * @return Factory
     */
    public function getFactory(): Factory
    {
        return $this->factory;
    }

    /**
     * @return ExpressionStringifier
     */
    public function getExpressionStringifier(): ExpressionStringifier
    {
        if (!$this->expressionStringifier) {
            $this->expressionStringifier = $this->createExpressionStringifier();
        }

        return $this->expressionStringifier;
    }

    /**
     * @return ConditionStringifier
     */
    public function getConditionStringifier(): ConditionStringifier
    {
        if (!$this->conditionStringifier) {
            $this->conditionStringifier = $this->createConditionStringifier();
        }

        return $this->conditionStringifier;
    }

    public function decorateParamForCondition(mixed $param, mixed $field): mixed
    {
        if ($param instanceof \VV\Db\Param) {
            return $param->setValue($this->decorateParamForCondition($param->getValue(), $field));
        }

        return ($o = $this->getFieldModel($field, $this->getQueryTableClause()))
            ? $o->prepeareValueForCondition($param)
            : $param;
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
        return $this->getExpressionStringifier()->stringifyExpression($expression, $params, $withAlias);
    }

    /**
     * @param mixed|SqlParam $param
     * @param array|null     $params
     *
     * @return string
     */
    public function stringifyParam(mixed $param, array|null &$params): string
    {
        return $this->getExpressionStringifier()->stringifyParam($param, $params);
    }

    /**
     * @param Expression $expression
     * @param array|null $params
     * @param bool       $withAlias
     *
     * @return string
     */
    public function stringifyColumn(Expression $expression, ?array &$params, bool $withAlias = false): string
    {
        if ($expression instanceof DbObject) {
            if (!$expression->getOwner()) { // obj without owner (without table alias)
                $tableClause = $this->getQueryTableClause();
                if (count($tableClause->getItems()) > 1) {
                    $expression->setOwner($tableClause->getMainTableAlias());
                }
            }
        }

        return $this->stringifyExpression($expression, $params, $withAlias);
    }

    /**
     * Returns supported by this stringifier ids of clauses
     *
     * @return int
     */
    abstract public function getSupportedClausesIds(): int;

    /**
     * @param array|null &$params
     *
     * @return string
     */
    abstract public function stringifyRaw(?array &$params): string;

    /**
     * @return TableClause
     */
    abstract public function getQueryTableClause(): TableClause;

    /**
     * @param mixed       $field
     * @param TableClause $tableClause
     *
     * @return Field|null
     */
    protected function getFieldModel(mixed $field, TableClause $tableClause): ?Field
    {
        if (!$field) {
            return null;
        }

        if ($field instanceof Field) {
            return $field;
        }

        if (!$field instanceof DbObject) {
            $field = DbObject::create($field);
            if (!$field) {
                return null;
            }
        }

        $owner = $field->getOwner();
        $table = $tableClause->getTableModelOrMain($owner ? $owner->getName() : null);
        if ($table) {
            return $table->getFields()->get($field->getName());
        }

        return null;
    }

    /**
     * @param Expression[] $exprList
     * @param array|null   $params
     * @param bool         $withAlias
     *
     * @return string
     */
    protected function stringifyColumnList(array $exprList, ?array &$params, bool $withAlias = false): string
    {
        $strings = [];
        foreach ($exprList as $expr) {
            $strings[] = $this->stringifyColumn($expr, $params, $withAlias);
        }

        return implode(', ', $strings);
    }

    /**
     * @param Condition $condition
     *
     * @return SqlPart
     */
    protected function buildConditionSql(Condition $condition): SqlPart
    {
        return $this->getConditionStringifier()->buildConditionSql($condition);
    }

    /**
     * @param Condition  $where
     * @param array|null $params
     *
     * @return string
     */
    protected function stringifyWhereClause(Condition $where, ?array &$params): string
    {
        if ($where->isEmpty()) {
            return '';
        }

        return ' WHERE ' . $this->buildConditionSql($where)->embed($params);
    }

    /**
     * @param TableClause $table
     *
     * @return SqlPart
     */
    protected function buildTableSql(TableClause $table): SqlPart
    {
        $conditionStringifier = $this->getConditionStringifier();

        $sql = '';
        $params = [];
        $useAlias = $this->useAliasForTable($table);
        foreach ($table->getItems() as $item) {
            $tableNameStr = $this->stringifyExpression($item->getTable(), $params, $useAlias);

            // todo: reconsider (move to method or something else)
            if ($useIdx = $item->getUseIndex()) {
                $tableNameStr .= ' USE INDEX (' . (is_array($useIdx) ? implode(', ', $useIdx) : $useIdx) . ') ';
            }

            if ($sql) {
                $on = $conditionStringifier->buildConditionSql($item->getJoinCondition());

                $sql .= " {$item->getJoinType()} $tableNameStr ON ({$on->embed($params)})";
            } else {
                $sql = $tableNameStr;
            }
        }

        return $this->createSqlPart($sql, $params);
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

    /**
     * @return ExpressionStringifier
     */
    protected function createExpressionStringifier(): ExpressionStringifier
    {
        return new ExpressionStringifier($this);
    }

    /**
     * @return ConditionStringifier
     */
    protected function createConditionStringifier(): ConditionStringifier
    {
        return new ConditionStringifier($this);
    }

    /**
     * @param string $sql
     *
     * @return string
     */
    protected function stringifyFinalDecorate(string $sql): string
    {
        return $sql;
    }

    /**
     * @param TableClause $table
     *
     * @return bool
     */
    protected function useAliasForTable(TableClause $table): bool
    {
        return true;
    }
}
