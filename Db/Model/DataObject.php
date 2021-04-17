<?php declare(strict_types=1);

/*
 * This file is part of the VV package.
 *
 * (c) Volodymyr Sarnytskyi <v00v4n@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
namespace VV\Db\Model;

use VV\Db\Sql;
use VV\Db\Sql\Condition;

/**
 * Class DataObject
 *
 * @package VV\Db\Model
 */
abstract class DataObject extends DbObject {

    public const DFLT_PREFIXES = ['tbl_', 'vw_', 't_', 'v_'];
    protected const DFLT_ALIAS = '';
    protected const FIELDS = [];

    private ?FieldList $fields = null;

    public function dfltAlias(): string {
        return static::DFLT_ALIAS;
    }

    /**
     * @return FieldList
     */
    public function fields(): FieldList {
        if ($this->fields === null) $this->fields = new FieldList(static::FIELDS);

        return $this->fields;
    }

    /**
     * Create select query
     *
     * @param string[]|\VV\Db\Sql\Expression[] $fields
     *
     * @return Sql\SelectQuery
     */
    public function select(...$fields): Sql\SelectQuery {
        return $this->connection()->select(...$fields)->from($this);
    }

    /**
     * @param string     $field
     * @param string|int $value
     * @param null       $fields
     * @param int|null   $fetchMode
     *
     * @return mixed
     */
    public function fetchByField(string $field, string|int $value, $fields = null, int $fetchMode = null): mixed {
        return $this->fetchByFields([$field => $value], $fields, $fetchMode);
    }

    /**
     * @param Condition|array|string $condition
     * @param string|string[]        $fields
     * @param int|null               $fetchMode
     *
     * @return mixed
     */
    public function fetchByFields(Condition|array|string $condition, $fields = null, int $fetchMode = null): mixed {
        $sql = $this->select(...(array)$fields)->where($condition);
        if ($fields && !is_array($fields)) return $sql->column(flags: $fetchMode);

        return $sql->row($fetchMode);
    }

    /**
     * @param string                      $valueField
     * @param string                      $keyField
     * @param Condition|array|string|null $condition
     * @param string|null                 $orderBy
     *
     * @return array
     */
    public function assoc(string $valueField, string $keyField, $condition = null, $orderBy = null): array {
        if (!$orderBy) $orderBy = $keyField;

        $fields = [$keyField];
        if ($valueField != $keyField) $fields[] = $valueField;

        return $this->select(...$fields)->where($condition)->orderBy($orderBy)->rows(null, $keyField, $valueField);
    }
}
