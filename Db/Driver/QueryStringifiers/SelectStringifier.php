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
use VV\Db\Sql;
use VV\Db\Sql\SelectQuery as SelectQuery;

/**
 * Class Select
 *
 * @package VV\Db\Driver\Sql\Stringifier
 */
class SelectStringifier extends QueryStringifier {

    private SelectQuery $selectQuery;

    /**
     * Select constructor.
     *
     * @param SelectQuery $selectQuery
     * @param Driver      $driver
     */
    public function __construct(SelectQuery $selectQuery, Driver $driver) {
        parent::__construct($driver);
        $this->selectQuery = $selectQuery;
    }

    public function supportedClausesIds() {
        return SelectQuery::C_COLUMNS
               | SelectQuery::C_TABLE
               | SelectQuery::C_WHERE
               | SelectQuery::C_GROUP_BY
               | SelectQuery::C_ORDER_BY
               | SelectQuery::C_HAVING
               | SelectQuery::C_LIMIT
               | SelectQuery::C_DISTINCT
               | SelectQuery::C_NOCACHE
               | SelectQuery::C_FOR_UPDATE
               | SelectQuery::C_HINT;
    }

    /**
     * @return SelectQuery
     */
    public function selectQuery() {
        return $this->selectQuery;
    }

    /**
     * @inheritDoc
     */
    public function queryTableClause() {
        return $this->selectQuery()->tableClause();
    }

    public function stringifyRaw(&$params) {
        $query = $this->selectQuery();
        $sql = $this->strSelectClause($query, $params)
               . $this->strFromClause($query->tableClause(), $params)
               . $this->strWhereClause($query->whereClause(), $params)
               . $this->strGroupByClause($query->groupByClause(), $params)
               . $this->strHavingClause($query->havingClause(), $params)
               . $this->strOrderByClause($query->orderByClause(), $params);

        $limit = $query->limitClause();
        if (!$limit->isEmpty()) {
            $this->applyLimitClause($sql, $limit->count(), $limit->offset());
        }

        if ($fuc = $query->forUpdateClause()) {
            $this->applyForUpdateClause($sql, $fuc);
        }

        return $sql;
    }

    protected function strSelectClause(Sql\SelectQuery $query, &$params): string {
        return 'SELECT '
               . (($hint = $query->hintClause()) ? $hint . ' ' : '')
               . ($query->isDistinct() ? 'DISTINCT ' : '')
               . $this->strColumnList($query->columnsClause()->items(), $params, true);
    }

    protected function strFromClause(Sql\Clauses\Table $table, &$params): string {
        return ' FROM ' . $this->buildTableSql($table)->embed($params);
    }

    protected function strGroupByClause(Sql\Clauses\GroupBy $groupBy, &$params): string {
        if ($groupBy->isEmpty()) return '';

        return ' GROUP BY ' . $this->strColumnList($groupBy->items(), $params);
    }

    protected function strHavingClause(Sql\Condition $having, &$params): string {
        if ($having->isEmpty()) return '';

        return ' HAVING ' . $this->buildConditionSql($having)->embed($params);
    }

    protected function strOrderByClause(Sql\Clauses\OrderBy $orderBy, &$params): string {
        if ($orderBy->isEmpty()) return '';

        return ' ORDER BY ' . $this->strOrderByItems($orderBy, $params);
    }

    protected function strOrderByItems(Sql\Clauses\OrderBy $orderBy, &$params): string {
        $orderStarr = [];

        foreach ($orderBy->items() as $item) {
            $str = $colstr = $this->strExpr($item->column(), $params);
            if ($item->isDesc()) $str .= ' DESC';

            $this->applyOderByItemNullsLast($str, $colstr, $item);

            $orderStarr[] = $str;
        }

        return implode(', ', $orderStarr);
    }

    protected function applyOderByItemNullsLast(&$str, $colstr, Sql\Clauses\OrderByItem $item): void {
        $isdesc = $item->isDesc();
        $isNullsLast = $item->isNullsLast();
        if ($isdesc == $isNullsLast) return;

        $isnullDirect = $isdesc ? 'ASC' : 'DESC';

        $str = "ISNULL($colstr) $isnullDirect, $str";
    }

    protected function applyLimitClause(&$sql, int $count, int $offset): void {
        $sql .= ' LIMIT ' . ($offset ? "$offset, " : '') . $count;
    }

    /**
     * @param string      $sql
     * @param string|bool $clause
     */
    protected function applyForUpdateClause(string &$sql, $clause): void {
        $sql .= ' FOR UPDATE' . (is_string($clause) ? " $clause" : '');
    }
}
