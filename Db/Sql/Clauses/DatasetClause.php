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
     * @param iterable|string|Expression $field
     * @param mixed|null                 $value
     *
     * @return $this
     */
    public function add(iterable|string|Expression $field, mixed $value = null): static
    {
        if ($field) {
            if (is_iterable($field)) {
                foreach ($field as $k => $v) {
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

                $this->setItem($field, $value);
            }

            return $this;
        }

        return $this;
    }

    /**
     * Returns tuple [$fields[], $values[]]
     *
     * @return array
     */
    public function split(): array
    {
        $fields = $values = [];
        foreach ($this->getItems() as $item) {
            $fields[] = $item->getField();
            $values[] = $item->getValue();
        }

        return [$fields, $values];
    }

    /**
     * @return array
     */
    public function map(): array
    {
        $map = [];
        foreach ($this->getItems() as $item) {
            $map[$item->getField()->getName()] = $item->getValue();
        }

        return $map;
    }

    /**
     * @param mixed $field
     * @param mixed $value
     *
     * @return $this
     */
    protected function setItem(mixed $field, mixed $value): static
    {
        if (is_array($value)) {
            $exploded = explode('=', $field);
            if (count($exploded) != 2) {
                throw new \InvalidArgumentException('For custom query $field must by with "=" symbol');
            }
            [$field, $expr] = $exploded;
            $value = Sql::plain($expr, $value);
        }

        $item = $this->creteItem($field, $value);
        $itemName = $item->getField()->getExpressionId();
        $this->items[$itemName] = $item;

        return $this;
    }

    /**
     * @param string|DbObject $field
     * @param mixed           $value
     *
     * @return DatasetClauseItem
     */
    protected function creteItem(string|DbObject $field, mixed $value): DatasetClauseItem
    {
        return new DatasetClauseItem($field, $value);
    }
}
