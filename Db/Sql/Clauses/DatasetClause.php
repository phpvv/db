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
use VV\Db\Sql\Expressions\DbObject;
use VV\Db\Sql\Expressions\Expression;

/**
 * Class DatasetClause
 *
 * @package VV\Db\Sql\Clauses
 * @method DatasetClauseItem[] getItems(): array
 */
class DatasetClause extends ItemList
{
    /**
     * @param iterable|string|Expression $column
     * @param mixed|null                 $value
     *
     * @return $this
     */
    public function add(iterable|string|Expression $column, mixed $value = null): static
    {
        if ($column) {
            if (is_iterable($column)) {
                foreach ($column as $k => $v) {
                    if (is_int($k)) {
                        $k = $v;
                        $v = [];
                    }
                    $this->setItem($k, $v);
                }
            } else {
                if (func_num_args() < 2) {
                    $value = [];
                }

                $this->setItem($column, $value);
            }

            return $this;
        }

        return $this;
    }

    /**
     * Returns tuple [$columns[], $values[]]
     *
     * @return array
     */
    public function split(): array
    {
        $columns = $values = [];
        foreach ($this->getItems() as $item) {
            $columns[] = $item->getColumn();
            $values[] = $item->getValue();
        }

        return [$columns, $values];
    }

    /**
     * @return array
     */
    public function map(): array
    {
        $map = [];
        foreach ($this->getItems() as $item) {
            $map[$item->getColumn()->getName()] = $item->getValue();
        }

        return $map;
    }

    /**
     * @param mixed $column
     * @param mixed $value
     *
     * @return $this
     */
    protected function setItem(mixed $column, mixed $value): static
    {
        if (is_array($value)) {
            $exploded = explode('=', $column);
            if (count($exploded) != 2) {
                throw new \InvalidArgumentException('For custom query $column must be with "=" symbol');
            }
            [$column, $expr] = $exploded;
            $value = Sql::plain($expr, $value);
        }

        $item = $this->creteItem($column, $value);
        $itemName = $item->getColumn()->getExpressionId();
        $this->items[$itemName] = $item;

        return $this;
    }

    /**
     * @param string|DbObject $column
     * @param mixed           $value
     *
     * @return DatasetClauseItem
     */
    protected function creteItem(string|DbObject $column, mixed $value): DatasetClauseItem
    {
        return new DatasetClauseItem($column, $value);
    }
}
