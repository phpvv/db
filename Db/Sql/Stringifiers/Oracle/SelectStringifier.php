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

namespace VV\Db\Sql\Stringifiers\Oracle;

use VV\Db\Sql\Clauses\OrderByClauseItem;

/**
 * Class Select
 *
 * @package VV\Db\Driver\Oracle\SqlStringifier
 */
class SelectStringifier extends \VV\Db\Sql\Stringifiers\SelectStringifier
{
    use CommonUtils;

    /**
     * @inheritDoc
     */
    protected function applyLimitClause(&$sql, int $count, int $offset): void
    {
        $rn = 'ora_rownum_field';
        $fields = '`' . implode('`, `', $this->getSelectQuery()->getColumnsClause()->getResultFields()) . '`';
        /** @noinspection SqlNoDataSourceInspection */
        $tableSql = "SELECT t.*, rownum AS $rn FROM ($sql) t";
        /** @noinspection SqlNoDataSourceInspection */
        $sql = "SELECT $fields FROM ($tableSql) WHERE $rn > $offset AND $rn <= $count + $offset";
    }

    /**
     * @inheritDoc
     */
    protected function applyOderByItemNullsLast(&$str, $columnString, OrderByClauseItem $item): void
    {
        $str .= ' NULLS ' . ($item->isNullsLast() ? 'LAST' : 'FIRST');
    }
}
