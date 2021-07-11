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
 * Class InsertFieldsClause
 *
 * @package VV\Db\Sql\Clauses
 * @method Sql\Expressions\DbObject[] items():array
 */
class InsertFieldsClause extends ColumnList
{

    protected function _add(array $columns)
    {
        foreach ($columns as &$col) {
            if ($o = Sql\Expressions\DbObject::create($col)) {
                $col = $o;
            } else {
                throw new \InvalidArgumentException();
            }
        }
        unset($col);

        $this->appendItems(...$columns);
    }

    protected function allowedObjectTypes(): array
    {
        return [Sql\Expressions\DbObject::class];
    }
}
