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

use VV\Db\Sql\Clauses\GroupByClause;
use VV\Db\Sql\Clauses\OrderByClause;
use VV\Db\Sql\Clauses\OrderByClauseItem;
use VV\Db\Sql\Clauses\TableClause;
use VV\Db\Sql\Condition;
use VV\Db\Sql\SelectQuery;

/**
 * Class Select
 *
 * @package VV\Db\Driver\Sql\Stringifier
 */
class SelectStringifier extends QueryStringifier
{
    private SelectQuery $selectQuery;

    /**
     * SelectStringifier constructor.
     *
     * @param SelectQuery $selectQuery
     * @param Factory     $factory
     */
    public function __construct(SelectQuery $selectQuery, Factory $factory)
    {
        parent::__construct($factory);
        $this->selectQuery = $selectQuery;
    }

    public function getSupportedClausesIds(): int
    {
        return SelectQuery::C_COLUMNS
               | SelectQuery::C_TABLE
               | SelectQuery::C_WHERE
               | SelectQuery::C_GROUP_BY
               | SelectQuery::C_ORDER_BY
               | SelectQuery::C_HAVING
               | SelectQuery::C_LIMIT
               | SelectQuery::C_DISTINCT
               | SelectQuery::C_FOR_UPDATE
               | SelectQuery::C_COMBINING;
    }

    /**
     * @return SelectQuery
     */
    public function getSelectQuery(): SelectQuery
    {
        return $this->selectQuery;
    }

    /**
     * @inheritDoc
     */
    public function getQueryTableClause(): TableClause
    {
        return $this->getSelectQuery()->getTableClause();
    }

    /**
     * @inheritDoc
     */
    public function stringifyRaw(?array &$params): string
    {
        $query = $this->getSelectQuery();
        if (!$query->getCombiningClause()->isEmpty()) {
            return $this->stringifyCombiningQuery($query, $params);
        }

        return $this->stringifySingleSelectQuery($query, $params);
    }

    protected function stringifySingleSelectQuery(SelectQuery $query, ?array &$params): string
    {
        $sql = $this->stringifySelectClause($query, $params)
               . $this->stringifyFromClause($query->getTableClause(), $params)
               . $this->stringifyWhereClause($query->getWhereClause(), $params)
               . $this->stringifyGroupByClause($query->getGroupByClause(), $params)
               . $this->stringifyHavingClause($query->getHavingClause(), $params)
               . $this->stringifyOrderByClause($query->getOrderByClause(), $params);

        $limit = $query->getLimitClause();
        if (!$limit->isEmpty()) {
            $this->applyLimitClause($sql, $limit->getCount(), $limit->getOffset());
        }

        if ($fuc = $query->getForUpdateClause()) {
            $this->applyForUpdateClause($sql, $fuc);
        }

        return $sql;
    }

    protected function stringifyCombiningQuery(SelectQuery $query, ?array &$params): string
    {
        $checkAllEmpty = [
            'ColumnsClause' => $query->getColumnsClause()->isAsterisk(),
            'TableClause' => $query->getTableClause()->isEmpty(),
            'WhereClause' => $query->getWhereClause()->isEmpty(),
            'GroupByClause' => $query->getGroupByClause()->isEmpty(),
            'HavingClause' => $query->getHavingClause()->isEmpty(),
        ];

        foreach ($checkAllEmpty as $clauseName => $isEmpty) {
            if (!$isEmpty) {
                throw new \LogicException("$clauseName is not allowed for entire combined query");
            }
        }

        $combiningClause = $query->getCombiningClause();

        $sql = '';
        foreach ($combiningClause->getItems() as $item) {
            if ($sql) {
                $sql .= " {$item->getConnector()} ";
                if ($item->isAll()) {
                    $sql .= 'ALL ';
                }
            }

            $unionStr = (new static($item->getQuery(), $this->getFactory()))->stringifyRaw($params);
            $sql .= "($unionStr)";
        }

        $sql .= $this->stringifyOrderByClause($query->getOrderByClause(), $params);

        $limit = $query->getLimitClause();
        if (!$limit->isEmpty()) {
            $this->applyLimitClause($sql, $limit->getCount(), $limit->getOffset());
        }

        return $sql;
    }

    /**
     * @param SelectQuery $query
     * @param array|null  $params
     *
     * @return string
     */
    protected function stringifySelectClause(SelectQuery $query, ?array &$params): string
    {
        return 'SELECT '
               . ($query->isDistinct() ? 'DISTINCT ' : '')
               . $this->stringifyColumnList($query->getColumnsClause()->getItems(), $params, true);
    }

    /**
     * @param TableClause $table
     * @param array|null  $params
     *
     * @return string
     */
    protected function stringifyFromClause(TableClause $table, ?array &$params): string
    {
        return ' FROM ' . $this->buildTableSql($table)->embed($params);
    }

    /**
     * @param GroupByClause $groupBy
     * @param array|null    $params
     *
     * @return string
     */
    protected function stringifyGroupByClause(GroupByClause $groupBy, ?array &$params): string
    {
        if ($groupBy->isEmpty()) {
            return '';
        }

        return ' GROUP BY ' . $this->stringifyColumnList($groupBy->getItems(), $params);
    }

    /**
     * @param Condition  $having
     * @param array|null $params
     *
     * @return string
     */
    protected function stringifyHavingClause(Condition $having, ?array &$params): string
    {
        if ($having->isEmpty()) {
            return '';
        }

        return ' HAVING ' . $this->buildConditionSql($having)->embed($params);
    }

    /**
     * @param OrderByClause $orderBy
     * @param array|null    $params
     *
     * @return string
     */
    protected function stringifyOrderByClause(OrderByClause $orderBy, ?array &$params): string
    {
        if ($orderBy->isEmpty()) {
            return '';
        }

        return ' ORDER BY ' . $this->stringifyOrderByItems($orderBy, $params);
    }

    /**
     * @param OrderByClause $orderBy
     * @param array|null    $params
     *
     * @return string
     */
    protected function stringifyOrderByItems(OrderByClause $orderBy, ?array &$params): string
    {
        $orderStarr = [];

        foreach ($orderBy->getItems() as $item) {
            $str = $columnString = $this->stringifyExpression($item->getExpression(), $params);
            if ($item->isDesc()) {
                $str .= ' DESC';
            }

            $this->applyOderByItemNullsLast($str, $columnString, $item);

            $orderStarr[] = $str;
        }

        return implode(', ', $orderStarr);
    }

    /**
     * @param                   $str
     * @param                   $columnString
     * @param OrderByClauseItem $item
     */
    protected function applyOderByItemNullsLast(&$str, $columnString, OrderByClauseItem $item): void
    {
        $isDesc = $item->isDesc();
        $isNullsLast = $item->isNullsLast();
        if ($isDesc == $isNullsLast) {
            return;
        }

        $isnullDirect = $isDesc ? 'ASC' : 'DESC';

        $str = "ISNULL($columnString) $isnullDirect, $str";
    }

    /**
     * @param     $sql
     * @param int $count
     * @param int $offset
     */
    protected function applyLimitClause(&$sql, int $count, int $offset): void
    {
        $sql .= ' LIMIT ' . ($offset ? "$offset, " : '') . $count;
    }

    /**
     * @param string      $sql
     * @param bool|string $clause
     */
    protected function applyForUpdateClause(string &$sql, bool|string $clause): void
    {
        $sql .= ' FOR UPDATE' . (is_string($clause) ? " $clause" : '');
    }
}
