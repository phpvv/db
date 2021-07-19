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
    protected const PK_FIELDS = [];
    protected const FOREIGN_KEYS = [];

    private ?ForeignKeyList $foreignKeys = null;

    public function getPk(): string
    {
        return static::PK;
    }

    public function getPkFields(): array
    {
        return static::PK_FIELDS;
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
        $query = $this->getConnection()->delete()->table($this);
        if (!$condition) {
            return $query;
        }

        if (is_scalar($condition)) {
            $condition = [static::PK => $condition];
        }

        return $query->where($condition)->affectedRows;
    }

    /**
     * @param string|int   $id
     * @param string|array $fields
     * @param int|null     $fetchMode
     *
     * @return mixed String if type of $fields is string and array if is array
     */
    public function fetchById(string|int $id, string|array $fields = [], int $fetchMode = null): mixed
    {
        if ((string)$id === '') {
            throw new \InvalidArgumentException('ID is empty');
        }

        return $this->fetchByField(static::PK, $id, $fields, $fetchMode) ?: null;
    }

    /**
     * Checks if there is record with $id
     *
     * @param string|int $id
     *
     * @return bool
     */
    public function checkById(string|int $id): bool
    {
        return (bool)$this->fetchById($id, explode(',', static::PK));
    }

    /**
     * Checks if there is record with this $field=$var
     *
     * @param string     $field
     * @param string|int $value
     *
     * @return bool
     */
    public function checkByField(string $field, string|int $value): bool
    {
        return (bool)$this->fetchByField($field, $value, explode(',', static::PK));
    }

    /**
     * Checks if there is record with this parameters
     *
     * @param Condition|array|string $condition
     *
     * @return bool
     */
    public function checkByFields(Condition|array|string $condition): bool
    {
        return (bool)$this->fetchByFields($condition, explode(',', static::PK));
    }

    /**
     * @inheritDoc
     */
    public function assoc(
        string $valueField,
        string $keyField = null,
        $condition = null,
        array|Expression|string $orderBy = null
    ): array {
        if (!$keyField) {
            $keyField = $this->getPk();
        }

        return parent::assoc($valueField, $keyField, $condition, $orderBy);
    }
}
