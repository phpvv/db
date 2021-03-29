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

/**
 * Class ColumnList
 *
 * @package VV\Db\Sql\Clauses
 */
abstract class ColumnList extends ItemList {

    /**
     * Add field(s)
     *
     * @param string[]|\VV\Db\Sql\Expression[] ...$columns
     *
     * @return $this
     */
    public function add(...$columns): static {
        if (!$columns) return $this;

        if (count($columns) == 1 && is_array($columns[0])) {
            $columns = $columns[0];
        }

        $allowedObjTypes = $this->allowedObjectTypes();
        foreach ($columns as $i => $col) {
            if (\VV\emt($col)) {
                throw new \InvalidArgumentException("Column #$i is empty");
            }

            if (is_object($col) && $allowedObjTypes) {
                if (!\VV\instOf($col, ...$allowedObjTypes)) {
                    throw new \InvalidArgumentException("Wrong type of column #$i");
                }
            } elseif (!is_scalar($col)) {
                throw new \InvalidArgumentException("Column #$i is not scalar type");
            }
        }

        $this->_add($columns);

        return $this;
    }

    abstract protected function _add(array $columns);

    abstract protected function allowedObjectTypes(): array;
}
