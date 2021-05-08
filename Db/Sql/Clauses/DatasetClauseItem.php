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
 * Class Item
 *
 * @package VV\Db\Sql\Clause\Dataset
 */
class DatasetClauseItem {

    /**
     * @var \VV\Db\Sql\DbObject
     */
    private $field;

    /**
     * @var mixed|Sql\Plain|Sql\SelectQuery|\VV\Db\Param
     */
    private $value;

    /**
     * Item constructor.
     *
     * @param \VV\Db\Sql\DbObject|string                   $field
     * @param Sql\Plain|Sql\SelectQuery|\VV\Db\Param|mixed $value
     *
     */
    public function __construct($field, $value = null) {
        $this->setField($field);
        if (func_num_args() > 1) $this->setValue($value);
    }

    /**
     * @return Sql\DbObject
     */
    public function field() {
        return $this->field;
    }

    /**
     * @return \VV\Db\Param|Sql\Plain|Sql\SelectQuery|mixed
     */
    public function value() {
        return $this->value;
    }

    /**
     * @param \VV\Db\Param|Sql\Plain|Sql\SelectQuery|mixed $value
     *
     * @return $this
     */
    public function setValue($value) {
        $this->value = $value;

        return $this;
    }

    /**
     * @param Sql\DbObject $field
     *
     * @return $this
     */
    protected function setField($field) {
        if (!$field instanceof Sql\DbObject) {
            $field = Sql\DbObject::create($field);
        }

        if (!$field) throw new \InvalidArgumentException;
        $this->field = $field;

        return $this;
    }
}
