<?php declare(strict_types=1);

/*
 * This file is part of the VV package.
 *
 * (c) Volodymyr Sarnytskyi <v00v4n@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
namespace VV\Db\Sql\Stringifiers\Postgres;

/**
 * Class SelectStringifier
 *
 * @package VV\Db\Postgres\QueryStringifiers
 */
class SelectStringifier extends \VV\Db\Sql\Stringifiers\SelectStringifier
{

    use CommonUtils;

    protected function applyLimitClause(&$sql, int $count, int $offset): void
    {
        $sql .= " LIMIT $count" . ($offset ? " OFFSET $offset" : '');
    }

    protected function applyOderByItemNullsLast(&$str, $colstr, \VV\Db\Sql\Clauses\OrderByClauseItem $item): void
    {
        $str .= ' NULLS ' . ($item->isNullsLast() ? 'LAST' : 'FIRST');
    }
}
