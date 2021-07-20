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

use VV\Db\Driver\Driver;
use VV\Db\Sql;
use VV\Db\Sql\SelectQuery as SelectQuery;

/**
 * Class Select
 *
 * @package VV\Db\Driver\Sql\Stringifier
 */
class SelectStringifier extends QueryStringifier
{

    private SelectQuery $selectQuery;

    /**
     * Select constructor.
     *
     * @param SelectQuery $selectQuery
     * @param Driver      $factory
     */
    public function __construct(SelectQuery $selectQuery, Factory $factory)
    {
        parent::__construct($factory);
        $this->selectQuery = $selectQuery;
    }

    public function supportedClausesIds()
    {
        return SelectQuery::C_COLUMNS
               | SelectQuery::C_TABLE
               | SelectQuery::C_WHERE
               | SelectQuery::C_GROUP_BY
               | SelectQuery::C_ORDER_BY
               | SelectQuery::C_HAVING
               | SelectQuery::C_LIMIT
               | SelectQuery::C_DISTINCT
               | SelectQuery::C_FOR_UPDATE;
    }

    /**
     * @return SelectQuery
     */
    public function selectQuery()
    {
        return $this->selectQuery;
    }

    /**
     * @inheritDoc
     */
    public function queryTableClause()
    {
        return $this->selectQuery()->getTableClause();
    }

    public function stringifyRaw(&$params)
    {
        $query = $this->selectQuery();
        $sql = $this->strSelectClause($query, $params)
               . $this->strFromClause($query->getTableClause(), $params)
               . $this->strWhereClause($query->getWhereClause(), $params)
               . $this->strGroupByClause($query->getGroupByClause(), $params)
               . $this->strHavingClause($query->getHavingClause(), $params)
               . $this->strOrderByClause($query->getOrderByClause(), $params);

        $limit = $query->getLimitClause();
        if (!$limit->isEmpty()) {
            $this->applyLimitClause($sql, $limit->getCount(), $limit->getOffset());
        }

        if ($fuc = $query->getForUpdateClause()) {
            $this->applyForUpdateClause($sql, $fuc);
        }

        return $sql;
    }

    protected function strSelectClause(Sql\SelectQuery $query, &$params): string
    {
        return 'SELECT '
               . ($query->isDistinct() ? 'DISTINCT ' : '')
               . $this->strColumnList($query->getColumnsClause()->getItems(), $params, true);
    }

    protected function strFromClause(Sql\Clauses\TableClause $table, &$params): string
    {
        return ' FROM ' . $this->buildTableSql($table)->embed($params);
    }

    protected function strGroupByClause(Sql\Clauses\GroupByClause $groupBy, &$params): string
    {
        if ($groupBy->isEmpty()) {
            return '';
        }

        return ' GROUP BY ' . $this->strColumnList($groupBy->getItems(), $params);
    }

    protected function strHavingClause(Sql\Condition $having, &$params): string
    {
        if ($having->isEmpty()) {
            return '';
        }

        return ' HAVING ' . $this->buildConditionSql($having)->embed($params);
    }

    protected function strOrderByClause(Sql\Clauses\OrderByClause $orderBy, &$params): string
    {
        if ($orderBy->isEmpty()) {
            return '';
        }

        return ' ORDER BY ' . $this->strOrderByItems($orderBy, $params);
    }

    protected function strOrderByItems(Sql\Clauses\OrderByClause $orderBy, &$params): string
    {
        $orderStarr = [];

        foreach ($orderBy->getItems() as $item) {
            $str = $colstr = $this->strExpr($item->getExpression(), $params);
            if ($item->isDesc()) {
                $str .= ' DESC';
            }

            $this->applyOderByItemNullsLast($str, $colstr, $item);

            $orderStarr[] = $str;
        }

        return implode(', ', $orderStarr);
    }

    protected function applyOderByItemNullsLast(&$str, $colstr, Sql\Clauses\OrderByClauseItem $item): void
    {
        $isdesc = $item->isDesc();
        $isNullsLast = $item->isNullsLast();
        if ($isdesc == $isNullsLast) {
            return;
        }

        $isnullDirect = $isdesc ? 'ASC' : 'DESC';

        $str = "ISNULL($colstr) $isnullDirect, $str";
    }

    protected function applyLimitClause(&$sql, int $count, int $offset): void
    {
        $sql .= ' LIMIT ' . ($offset ? "$offset, " : '') . $count;
    }

    /**
     * @param string      $sql
     * @param string|bool $clause
     */
    protected function applyForUpdateClause(string &$sql, $clause): void
    {
        $sql .= ' FOR UPDATE' . (is_string($clause) ? " $clause" : '');
    }
}
