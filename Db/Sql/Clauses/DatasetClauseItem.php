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

use VV\Db\Param;
use VV\Db\Sql\Expressions\DbObject;
use VV\Db\Sql\Expressions\Expression;

/**
 * Class DatasetClauseItem
 *
 * @package VV\Db\Sql\Clauses
 */
class DatasetClauseItem
{
    private DbObject $field;
    /** @var mixed|Expression|Param */
    private mixed $value;

    /**
     * Item constructor.
     *
     * @param string|DbObject        $field
     * @param mixed|Expression|Param $value
     *
     */
    public function __construct(string|DbObject $field, mixed $value = null)
    {
        $this->setField($field);
        if (func_num_args() > 1) {
            $this->setValue($value);
        }
    }

    /**
     * @return DbObject
     */
    public function getField(): DbObject
    {
        return $this->field;
    }

    /**
     * @return mixed
     */
    public function getValue(): mixed
    {
        return $this->value;
    }

    /**
     * @param DbObject|string $field
     *
     * @return $this
     */
    protected function setField(DbObject|string $field): static
    {
        if (!$field instanceof DbObject) {
            $field = DbObject::create($field);
        }

        if (!$field) {
            throw new \InvalidArgumentException();
        }
        $this->field = $field;

        return $this;
    }

    /**
     * @param mixed|Expression|Param $value
     *
     * @return $this
     */
    public function setValue(mixed $value): static
    {
        $this->value = $value;

        return $this;
    }
}
