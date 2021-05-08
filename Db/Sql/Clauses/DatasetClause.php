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

use VV\Db\Sql\Expression;

/**
 * Class Dataset
 *
 * @package VV\Db\Sql\Clauses
 * @method \VV\Db\Sql\Clauses\DatasetClauseItem[] items():array
 */
class DatasetClause extends ItemList {

    /**
     * @ussed
     *
     * @param string|iterable|Expression $field
     * @param null                       $value
     *
     * @return $this
     */
    public function add(iterable|string|Expression $field, $value = null): static {
        if ($field) {
            if (is_iterable($field)) {
                foreach ($field as $k => $v) {
                    if (is_int($k)) {
                        $k = $v;
                        $v = [];
                    }
                    $this->_setItem($k, $v);
                }
            } else {
                if (func_num_args() < 2) {
                    $value = [];
                }

                $this->_setItem($field, $value);
            }

            return $this;
        }

        return $this;
    }

    public function split(): array {
        $fields = $values = [];
        foreach ($this->items() as $item) {
            $fields[] = $item->field();
            $values[] = $item->value();
        }

        return [$fields, $values];
    }

    /**
     * @return mixed
     */
    public function fieldsNamesValuesMap() {
        $map = [];
        /** @var \VV\Db\Sql\Clauses\DatasetClauseItem $item */
        foreach ($this->items() as $item) {
            $map[$item->field()->name()] = $item->value();
        }

        return $map;
    }

    protected function _setItem($field, $value) {
        if (is_array($value)) {
            [$field, $expr] = explode('=', $field);
            $value = \VV\Db\Sql::plain($expr, $value);
        }

        $item = $this->creteItem($field, $value);
        $itemName = $item->field()->exprId();
        $this->items[$itemName] = $item;
    }

    protected function creteItem($field, $value): DatasetClauseItem {
        return new DatasetClauseItem($field, $value);
    }
}
