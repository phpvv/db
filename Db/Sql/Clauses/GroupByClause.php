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

namespace VV\Db\Sql\Clauses;

use VV\Db\Sql;
use VV\Db\Sql\Expressions\Expression;

/**
 * Class GroupByClause
 *
 * @package VV\Db\Sql\Clauses
 * @method Expression[] getItems(): array
 */
class GroupByClause extends ColumnList
{
    /**
     * @inheritDoc
     */
    protected function addColumnArray(array $columns): void
    {
        foreach ($columns as $col) {
            $this->appendItems(Sql::expression($col));
        }
    }

    /**
     * @inheritDoc
     */
    protected function getAllowedObjectTypes(): array
    {
        return [Expression::class];
    }
}
