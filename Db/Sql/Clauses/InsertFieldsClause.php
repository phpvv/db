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

use VV\Db\Sql\Expressions\DbObject;

/**
 * Class InsertFieldsClause
 *
 * @package VV\Db\Sql\Clauses
 * @method DbObject[] getItems(): array
 */
class InsertFieldsClause extends ColumnList
{

    protected function addColumnArray(array $columns): void
    {
        foreach ($columns as &$col) {
            if (!$o = DbObject::create($col)) {
                throw new \InvalidArgumentException();
            }
            $col = $o;
        }
        unset($col);

        $this->appendItems(...$columns);
    }

    protected function getAllowedObjectTypes(): array
    {
        return [DbObject::class];
    }
}
