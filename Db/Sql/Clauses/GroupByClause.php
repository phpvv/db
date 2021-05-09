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
 * Class GroupByClause
 *
 * @package VV\Db\Sql\Clauses
 * @method Sql\Expressions\Expression[] items():array
 */
class GroupByClause extends ColumnList {

    protected function _add(array $columns) {
        foreach ($columns as $col) {
            $this->appendItems(Sql::expression($col));
        }
    }

    protected function allowedObjectTypes(): array {
        return [Sql\Expressions\Expression::class];
    }
}
