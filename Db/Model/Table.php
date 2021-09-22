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

use VV\Db\Sql\Condition;
use VV\Db\Sql\DeleteQuery;
use VV\Db\Sql\Expressions\Expression;
use VV\Db\Sql\InsertQuery;
use VV\Db\Sql\Predicates\Predicate;
use VV\Db\Sql\UpdateQuery;

/**
 * Class Table
 *
 * @package VV\Db\Model
 */
abstract class Table extends DataObject
{
    public const DFLT_PREFIXES = ['tbl_', 't_'];

    protected const PK = '';
    protected const PK_COLUMNS = [];
    protected const FOREIGN_KEYS = [];

    private ?ForeignKeyList $foreignKeys = null;

    public function getPk(): string
    {
        return static::PK;
    }

    public function getPkColumns(): array
    {
        return static::PK_COLUMNS;
    }

    /**
     * @return ForeignKeyList
     */
    public function getForeignKeys(): ForeignKeyList
    {
        if ($this->foreignKeys === null) {
            $this->foreignKeys = new ForeignKeyList(static::FOREIGN_KEYS);
        }

        return $this->foreignKeys;
    }

    /**
     * @param array|null $data
     *
     * @return InsertQuery|string|int
     */
    public function insert(array $data = null): InsertQuery|string|int
    {
        $insert = $this->getConnection()->insert()->into($this);
        if ($data === null) {
            return $insert;
        }

        return $insert->set($data)->insertedId();
    }

    /**
     * Update table row(s)
     *
     * @param mixed|array                                $data
     * @param array|int|string|Expression|Predicate|null $condition
     *
     * @return UpdateQuery|int
     */
    public function update(mixed $data = [], Predicate|Expression|array|int|string $condition = null): UpdateQuery|int
    {
        $query = $this->getConnection()->update()->table($this)->set($data);
        if (!$condition) {
            return $query;
        }

        if (is_scalar($condition)) {
            $condition = [static::PK => $condition];
        }

        return $query->where($condition)->affectedRows;
    }

    /**
     * Creates Delete Query or immediately deletes rows by $condition
     *
     * @param Condition|array|string|int|null $condition Condition for immediately deletion
     *
     * @return DeleteQuery|int Delete Query or number of deleted rows
     */
    public function delete(Condition|array|int|string $condition = null): DeleteQuery|int
    {
        $query = $this->getConnection()->delete()->from($this);
        if (!$condition) {
            return $query;
        }

        if (is_scalar($condition)) {
            $condition = [static::PK => $condition];
        }

        return $query->where($condition)->affectedRows;
    }

    /**
     * Selects single row by ID column(s)
     *
     * @param string|int   $id
     * @param string|array $columns
     * @param int|null     $fetchMode
     *
     * @return mixed Data of $columns or null if nothing was found.
     *               If $columns is string returns single value instead of array.
     */
    public function fetchById(string|int $id, string|array $columns = [], int $fetchMode = null): mixed
    {
        if ((string)$id === '') {
            throw new \InvalidArgumentException('ID is empty');
        }

        return $this->fetchByColumn(static::PK, $id, $columns, $fetchMode) ?: null;
    }

    /**
     * Checks if record with $id exists
     *
     * @param string|int $id
     *
     * @return bool
     */
    public function checkById(string|int $id): bool
    {
        return $this->checkByColumn(static::PK, $id);
    }

    /**
     * @inheritDoc
     */
    public function assoc(
        string $valueColumn,
        string $keyColumn = null,
        string|int|Expression|Predicate|array $condition = null,
        array|Expression|string $orderBy = null
    ): array {
        if (!$keyColumn) {
            $keyColumn = $this->getPk();
        }

        return parent::assoc($valueColumn, $keyColumn, $condition, $orderBy);
    }
}
