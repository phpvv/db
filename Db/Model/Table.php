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
 * Class Table
 *
 * @package VV\Db\Model
 */
abstract class Table extends DataObject {

    public const DFLT_PREFIXES = ['tbl_', 't_'];

    protected const PK = '';
    protected const PK_FIELDS = [];
    protected const FOREING_KEYS = [];

    private ?ForeignKeyList $foreignKeys = null;

    public function pk(): string {
        return static::PK;
    }

    public function pkFields(): array {
        return static::PK_FIELDS;
    }

    /**
     * @return ForeignKeyList
     */
    public function foreignKeys(): ForeignKeyList {
        if ($this->foreignKeys === null) $this->foreignKeys = new ForeignKeyList(static::FOREING_KEYS);

        return $this->foreignKeys;
    }

    /**
     * @param array|null $data
     *
     * @return Sql\InsertQuery|string|int
     */
    public function insert(array $data = null): Sql\InsertQuery|string|int {
        $insert = $this->connection()->insert()->into($this);
        if (!$data) return $insert;

        return $insert->set($data)->insertedId();
    }

    /**
     * Update table row(s)
     *
     * @param mixed                           $data
     * @param Condition|array|string|int|null $condition
     *
     * @return Sql\UpdateQuery|int
     */
    public function update($data = [], $condition = null): Sql\UpdateQuery|int {
        $q = $this->connection()->update()->table($this)->set($data);
        if (!$condition) return $q;

        if (is_scalar($condition)) $condition = [static::PK => $condition];

        return $q->where($condition)->affectedRows;
    }

    /**
     * Creates Delete Query or immediately deletes rows by $condition
     *
     * @param Condition|array|string|int|null $condition Condition for immediately deletion
     *
     * @return Sql\DeleteQuery|int Delete Query or number of deleted rows
     */
    public function delete(Condition|array|int|string $condition = null): Sql\DeleteQuery|int {
        $query = $this->connection()->delete()->table($this);
        if (!$condition) return $query;

        if (is_scalar($condition)) $condition = [static::PK => $condition];

        return $query->where($condition)->affectedRows;
    }

    /**
     * @param string|int   $id
     * @param string|array $fields
     * @param int|null     $fetchMode
     *
     * @return mixed String if type of $fields is string and array if is array
     */
    public function fetchById(string|int $id, string|array $fields = [], int $fetchMode = null): mixed {
        if ((string)$id === '') throw new \InvalidArgumentException('ID is empty');

        return $this->fetchByField(static::PK, $id, $fields, $fetchMode) ?: null;
    }

    /**
     * Checks if there is record with $id
     *
     * @param string|int $id
     *
     * @return bool
     */
    public function checkById(string|int $id): bool {
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
    public function checkByField(string $field, string|int $value): bool {
        return (bool)$this->fetchByField($field, $value, explode(',', static::PK));
    }

    /**
     * Checks if there is record with this parameters
     *
     * @param Condition|array|string $condition
     *
     * @return bool
     */
    public function checkByFields(Condition|array|string $condition): bool {
        return (bool)$this->fetchByFields($condition, explode(',', static::PK));
    }

    /**
     * @inheritDoc
     */
    public function assoc(string $valueField, string $keyField = null, $condition = null, $orderBy = null): array {
        if (!$keyField) $keyField = $this->pk();

        return parent::assoc($valueField, $keyField, $condition, $orderBy);
    }
}
