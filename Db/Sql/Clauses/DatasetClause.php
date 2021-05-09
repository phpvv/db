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
use VV\Db\Sql\Expressions\Expression;

/**
 * Class DatasetClause
 *
 * @package VV\Db\Sql\Clauses
 * @method \VV\Db\Sql\Clauses\DatasetClauseItem[] items():array
 */
class DatasetClause extends ItemList {

    /**
     * @ussed
     *
     * @param string|iterable|\VV\Db\Sql\Expressions\Expression    $field
     * @param mixed|\VV\Db\Sql\Expressions\Expression|\VV\Db\Param $value
     *
     * @return $this
     */
    public function add(iterable|string|Expression $field, mixed $value = null): static {
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
    public function split(): array {
        $fields = $values = [];
        foreach ($this->items() as $item) {
            $fields[] = $item->field();
            $values[] = $item->value();
        }

        return [$fields, $values];
    }

    /**
     * @return array
     */
    public function fieldsNamesValuesMap(): array {
        $map = [];
        foreach ($this->items() as $item) {
            $map[$item->field()->name()] = $item->value();
        }

        return $map;
    }

    /**
     * @param mixed $field
     * @param mixed $value
     *
     * @return $this
     */
    protected function setItem(mixed $field, mixed $value): static {
        if (is_array($value)) {
            [$field, $expr] = explode('=', $field);
            $value = \VV\Db\Sql::plain($expr, $value);
        }

        $item = $this->creteItem($field, $value);
        $itemName = $item->field()->exprId();
        $this->items[$itemName] = $item;

        return $this;
    }

    /**
     * @param string|DbObject $field
     * @param mixed           $value
     *
     * @return DatasetClauseItem
     */
    protected function creteItem(string|DbObject $field, mixed $value): DatasetClauseItem {
        return new DatasetClauseItem($field, $value);
    }
}
