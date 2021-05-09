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
 * Class DatasetClauseItem
 *
 * @package VV\Db\Sql\Clauses
 */
class DatasetClauseItem {

    private DbObject $field;
    /** @var mixed|\VV\Db\Sql\Expressions\Expression|\VV\Db\Param */
    private mixed $value;

    /**
     * Item constructor.
     *
     * @param string|DbObject                                      $field
     * @param mixed|\VV\Db\Sql\Expressions\Expression|\VV\Db\Param $value
     *
     */
    public function __construct(string|DbObject $field, mixed $value = null) {
        $this->setField($field);
        if (func_num_args() > 1) $this->setValue($value);
    }

    /**
     * @return DbObject
     */
    public function field(): DbObject {
        return $this->field;
    }

    /**
     * @return mixed
     */
    public function value(): mixed {
        return $this->value;
    }

    /**
     * @param mixed|\VV\Db\Sql\Expressions\Expression|\VV\Db\Param $value
     *
     * @return $this
     */
    public function setValue(mixed $value): static {
        $this->value = $value;

        return $this;
    }

    /**
     * @param DbObject|string $field
     *
     * @return $this
     */
    protected function setField(DbObject|string $field): static {
        if (!$field instanceof DbObject) {
            $field = DbObject::create($field);
        }

        if (!$field) throw new \InvalidArgumentException;
        $this->field = $field;

        return $this;
    }
}
