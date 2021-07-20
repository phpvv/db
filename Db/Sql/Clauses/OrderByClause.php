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

use VV\Db\Sql\Expressions\Expression;

/**
 * Class OrderByClause
 *
 * @package VV\Db\Sql\Clauses
 * @method OrderByClauseItem[] getItems(): array
 */
class OrderByClause extends ColumnList
{
    /**
     * @inheritDoc
     */
    protected function addColumnArray(array $columns): void
    {
        foreach ($columns as $col) {
            if (!$item = OrderByClauseItem::create($col)) {
                throw new \InvalidArgumentException();
            }

            $this->appendItems($item);
        }
    }

    /**
     * @inheritDoc
     */
    protected function getAllowedObjectTypes(): array
    {
        return [OrderByClauseItem::class, Expression::class];
    }
}
