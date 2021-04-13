<?php declare(strict_types=1);

/*
 * This file is part of the VV package.
 *
 * (c) Volodymyr Sarnytskyi <v00v4n@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
namespace VV;

use VV\Db\Exceptions\ConnectionError;
use VV\Db\Sql;

/**
 * Class Db
 *
 * @package VV
 */
abstract class Db {

    public const FETCH_ASSOC = 0x01;

    public const FETCH_NUM = 0x02;

    public const FETCH_BOTH = self::FETCH_ASSOC | self::FETCH_NUM;

    public const FETCH_OBJ = 0x04;

    public const FETCH_LOB_NOT_LOAD = 0x08; // only for oracle yet

    /** @var \VV\Db\Connection[] */
    private array $connections = [];

    private ?Db\Model\TableList $tables = null;

    private ?Db\Model\ViewList $views = null;

    public function __get($name): mixed {
        return match ($name) {
            'tbl' => $this->tables(),
            'vw' => $this->views(),
            default => throw new \LogicException,
        };
    }

    /**
     * @return Db\Connection
     */
    public function connection(): Db\Connection {
        $connection = &$this->connections[0];
        if (!$connection) $connection = $this->createConnection();

        return $connection;
    }

    public function transactionFreeConnection(): Db\Connection {
        foreach ($this->connections as $connection) {
            if (!$connection->isBusy()) {
                return $connection;
            }
        }

        $this->connections[] = ($connection = $this->createConnection());

        return $connection;
    }

    public function isInTransaction(): bool {
        foreach ($this->connections as $connection) {
            if ($connection->isBusy()) return true;
        }

        return false;
    }

    /**
     * @param          $sql
     * @param array    $params
     * @param int|null $flags
     *
     * @return Db\Result
     */
    public function query($sql, $params = [], int $flags = null): Db\Result {
        return $this->connection()->query($sql, $params, $flags);
    }

    /**
     * @param $sql
     *
     * @return Db\Statement
     */
    public function prepare($sql): Db\Statement {
        return $this->connection()->prepare($sql);
    }

    /**
     * @return Db\Transaction
     */
    public function startTransaction(): Db\Transaction {
        return $this->connection()->startTransaction();
    }

    /**
     * @return \VV\Db\Model\TableList
     */
    public function tables(): \VV\Db\Model\TableList {
        if (!$this->tables) $this->tables = $this->createTableList();

        return $this->tables;
    }

    /**
     * @return \VV\Db\Model\ViewList
     */
    public function views(): \VV\Db\Model\ViewList {
        if (!$this->views) $this->views = $this->createViewList();

        return $this->views;
    }

    /**
     * Create select query
     *
     * @param string[]|\VV\Db\Sql\Expression[] $columns
     *
     * @return Sql\SelectQuery
     */
    public function select(...$columns): Sql\SelectQuery {
        return $this->connection()->select(...$columns);
    }

    /**
     * Create insert query
     *
     * @param array|null $data
     *
     * @return Sql\InsertQuery
     */
    public function insert(array $data = null): Sql\InsertQuery {
        return $this->connection()->insert($data);
    }

    /**
     * Create update query
     *
     * @param array|null $data
     *
     * @return Sql\UpdateQuery
     */
    public function update(array $data = null): Sql\UpdateQuery {
        return $this->connection()->update($data);
    }

    /**
     * Create delete query
     *
     * @return Sql\DeleteQuery
     */
    public function delete(): Sql\DeleteQuery {
        return $this->connection()->delete();
    }

    /**
     * @return ConnectionError|null
     */
    public function connectionError(): ?ConnectionError {
        return $this->connection()->connectionError();
    }

    abstract public function createConnection(): Db\Connection;

    /**
     * @return \VV\Db\Model\TableList
     */
    protected function createTableList(): \VV\Db\Model\TableList {
        $cls = get_class($this) . '\TableList';

        return new $cls($this);
    }

    /**
     * @return \VV\Db\Model\ViewList
     */
    protected function createViewList(): \VV\Db\Model\ViewList {
        $cls = get_class($this) . '\ViewLs';

        return new $cls($this);
    }

    /**
     * Returns instance of DB
     *
     * @return static
     */
    public static function instance(): static {
        static $instances = [];

        $instance = &$instances[static::class];
        if (!$instance) $instance = new static;

        return $instance;
    }
}
