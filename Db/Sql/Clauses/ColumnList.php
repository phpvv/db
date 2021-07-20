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

use function VV\instOf;

/**
 * Class ColumnList
 *
 * @package VV\Db\Sql\Clauses
 */
abstract class ColumnList extends ItemList
{

    /**
     * Add field(s)
     *
     * @param string|array|Expression ...$columns
     *
     * @return $this
     */
    public function add(string|array|Expression ...$columns): static
    {
        if (!$columns) {
            return $this;
        }

        if (count($columns) == 1 && is_array($columns[0])) {
            $columns = $columns[0];
        }

        $allowedObjTypes = $this->getAllowedObjectTypes();
        foreach ($columns as $i => $col) {
            if (\VV\emt($col)) {
                throw new \InvalidArgumentException("Column #$i is empty");
            }

            if (is_object($col) && $allowedObjTypes) {
                if (!instOf($col, ...$allowedObjTypes)) {
                    throw new \InvalidArgumentException("Wrong type of column #$i");
                }
            } elseif (!\is_scalar($col)) {
                throw new \InvalidArgumentException("Column #$i is not scalar type");
            }
        }

        $this->addColumnArray($columns);

        return $this;
    }

    /**
     * @param array $columns
     *
     * @return void
     */
    abstract protected function addColumnArray(array $columns): void;

    /**
     * @return array
     */
    abstract protected function getAllowedObjectTypes(): array;
}
