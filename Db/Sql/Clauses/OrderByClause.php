<?php declare(strict_types=1);

/*
 * This file is part of the VV package.
 *
 * (c) Volodymyr Sarnytskyi <v00v4n@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
namespace VV\Db\Sql\Clauses;

use VV\Db\Sql;

/**
 * Class OrderBy
 *
 * @package VV\Db\Sql\Clauses
 * @method OrderByItemClause[] items():array
 */
class OrderByClause extends ColumnList {

    protected function _add(array $columns) {
        foreach ($columns as $col) {
            if ($item = OrderByItemClause::create($col)) {
                $this->appendItems($item);
            } else {
                throw new \InvalidArgumentException;
            }
        }
    }

    protected function allowedObjectTypes(): array {
        return [OrderByItemClause::class, Sql\Expression::class];
    }
}
