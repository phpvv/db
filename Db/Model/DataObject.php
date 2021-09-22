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
    protected const COLUMNS = [];

    private ?ColumnList $columns = null;

    public function getDefaultAlias(): string
    {
        return static::DFLT_ALIAS;
    }

    /**
     * @return ColumnList
     */
    public function getColumns(): ColumnList
    {
        if ($this->columns === null) {
            $this->columns = new ColumnList(static::COLUMNS);
        }

        return $this->columns;
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
     * Selects single row by (unique) $value of $column
     *
     * @param string            $column  Column for equality condition
     * @param string|int        $value   Value for equality condition
     * @param array|string|null $columns Column(s to SELECT
     * @param int|null          $flags
     *
     * @return mixed Data of $columns or null if nothing was found.
     *               If columns is string returns single value instead of array.
     */
    public function fetchByColumn(
        string $column,
        string|int $value,
        array|string $columns = null,
        int $flags = null
    ): mixed {
        return $this->fetchByCondition(Sql::condition($column)->eq($value), $columns, $flags);
    }

    /**
     * Selects single row by (unique) $condition
     *
     * @param Predicate|array|string $condition
     * @param string|string[]|null   $columns
     * @param int|null               $flags
     *
     * @return mixed
     */
    public function fetchByCondition(
        Predicate|array|string $condition,
        array|string $columns = null,
        int $flags = null
    ): mixed {
        $sql = $this->select(...(array)$columns)->where($condition);
        if ($columns && !is_array($columns)) {
            return $sql->column(flags: $flags);
        }

        return $sql->row($flags);
    }

    /**
     * Checks if record exists by $column=$value
     *
     * @param string     $column
     * @param string|int $value
     *
     * @return bool
     */
    public function checkByColumn(string $column, string|int $value): bool
    {
        return $this->checkByCondition([$column => $value]);
    }

    /**
     *  Checks if record exists by $condition
     *
     * @param Condition|array|string $condition
     *
     * @return bool
     */
    public function checkByCondition(Condition|array|string $condition): bool
    {
        return (bool)$this->select('COUNT(*)')->where($condition)->column();
    }

    /**
     * @param string                                       $valueColumn
     * @param string                                       $keyColumn
     * @param string|int|Expression|Predicate|array|null   $condition
     * @param string|Expression|Expression[]|string[]|null $orderBy
     *
     * @return array
     */
    public function assoc(
        string $valueColumn,
        string $keyColumn,
        string|int|Expression|Predicate|array $condition = null,
        array|Expression|string $orderBy = null
    ): array {
        if (!$orderBy) {
            $orderBy = $keyColumn;
        }

        $columns = [$keyColumn];
        if ($valueColumn != $keyColumn) {
            $columns[] = $valueColumn;
        }
        if (!is_array($orderBy)) {
            $orderBy = [$orderBy];
        }

        return $this->select(...$columns)
            ->where($condition)
            ->orderBy(...$orderBy)
            ->rows(null, $keyColumn, $valueColumn);
    }
}
