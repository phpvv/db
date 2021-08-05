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

namespace VV\Db\Sql\Stringifiers\Postgres;

use VV\Db\Sql\Clauses\OrderByClauseItem;

/**
 * Class SelectStringifier
 *
 * @package VV\Db\Postgres\QueryStringifiers
 */
class SelectStringifier extends \VV\Db\Sql\Stringifiers\SelectStringifier
{
    use CommonUtils;

    /**
     * @inheritDoc
     */
    protected function applyLimitClause(&$sql, int $count, int $offset): void
    {
        $sql .= " LIMIT $count" . ($offset ? " OFFSET $offset" : '');
    }

    /**
     * @inheritDoc
     */
    protected function applyOderByItemNullsLast(&$str, $columnString, OrderByClauseItem $item): void
    {
        $str .= ' NULLS ' . ($item->isNullsLast() ? 'LAST' : 'FIRST');
    }
}
