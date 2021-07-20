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

namespace VV\Db\Model;

use VV\Db\Sql;
use VV\Db\Sql\Condition;
use VV\Db\Sql\Expressions\Expression;
use VV\Db\Sql\Predicates\Predicate;

/**
 * Class DataObject
 *
 * @package VV\Db\Model
 */
abstract class DataObject extends DbObject
{
    public const DFLT_PREFIXES = ['tbl_', 'vw_', 't_', 'v_'];
    protected const DFLT_ALIAS = '';
    protected const FIELDS = [];

    private ?FieldList $fields = null;

    public function getDefaultAlias(): string
    {
        return static::DFLT_ALIAS;
    }

    /**
     * @return FieldList
     */
    public function getFields(): FieldList
    {
        if ($this->fields === null) {
            $this->fields = new FieldList(static::FIELDS);
        }

        return $this->fields;
    }

    /**
     * Create select query
     *
     * @param string[]|Expression[] $columns
     *
     * @return Sql\SelectQuery
     */
    public function select(...$columns): Sql\SelectQuery
    {
        return $this->getConnection()->select(...$columns)->from($this);
    }

    /**
     * @param string            $field
     * @param string|int        $value
     * @param array|string|null $columns
     * @param int|null          $fetchMode
     *
     * @return mixed
     */
    public function fetchByField(
        string $field,
        string|int $value,
        array|string $columns = null,
        int $fetchMode = null
    ): mixed {
        return $this->fetchByFields([$field => $value], $columns, $fetchMode);
    }

    /**
     * @param Predicate|array|string $condition
     * @param string|string[]|null   $columns
     * @param int|null               $fetchMode
     *
     * @return mixed
     */
    public function fetchByFields(
        Predicate|array|string $condition,
        array|string $columns = null,
        int $fetchMode = null
    ): mixed {
        $sql = $this->select(...(array)$columns)->where($condition);
        if ($columns && !is_array($columns)) {
            return $sql->column(flags: $fetchMode);
        }

        return $sql->row($fetchMode);
    }

    /**
     * @param string                                       $valueField
     * @param string                                       $keyField
     * @param string|int|array|Expression|Predicate|null   $condition
     * @param string|Expression|Expression[]|string[]|null $orderBy
     *
     * @return array
     */
    public function assoc(
        string $valueField,
        string $keyField,
        string|int|array|Expression|Predicate|null $condition = null,
        array|Expression|string $orderBy = null
    ): array {
        if (!$orderBy) {
            $orderBy = $keyField;
        }

        $fields = [$keyField];
        if ($valueField != $keyField) {
            $fields[] = $valueField;
        }
        if (!is_array($orderBy)) {
            $orderBy = [$orderBy];
        }

        return $this->select(...$fields)
            ->where($condition)
            ->orderBy(...$orderBy)
            ->rows(null, $keyField, $valueField);
    }
}
