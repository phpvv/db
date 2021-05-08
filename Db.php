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

use VV\Db\Connection;
use VV\Db\Exceptions\ConnectionError;
use VV\Db\Model\TableList;
use VV\Db\Model\ViewList;
use VV\Db\Result;
use VV\Db\Sql;
use VV\Db\Sql\Query;
use VV\Db\Statement;
use VV\Db\Transaction;

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

    /** @var Connection[] */
    private array $connections = [];
    private ?TableList $tables = null;
    private ?ViewList $views = null;

    public function __get($name): mixed {
        return match ($name) {
            'tbl' => $this->tables(),
            'vw' => $this->views(),
            default => throw new \LogicException,
        };
    }

    /**
     * @return Connection
     */
    public function connection(): Connection {
        $connection = &$this->connections[0];
        if (!$connection) $connection = $this->createConnection();

        return $connection;
    }

    public function transactionFreeConnection(): Connection {
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
     * @param Query|string $query
     * @param array|null   $params
     * @param int|null     $flags
     *
     * @return Result
     */
    public function query(Query|string $query, array $params = null, int $flags = null): Result {
        return $this->connection()->query($query, $params, $flags);
    }

    /**
     * @param Query|string $query
     *
     * @return Statement
     */
    public function prepare(Query|string $query): Statement {
        return $this->connection()->prepare($query);
    }

    /**
     * @return Transaction
     */
    public function startTransaction(): Transaction {
        return $this->connection()->startTransaction();
    }

    /**
     * @return TableList
     */
    public function tables(): TableList {
        if (!$this->tables) $this->tables = $this->createTableList();

        return $this->tables;
    }

    /**
     * @return ViewList
     */
    public function views(): ViewList {
        if (!$this->views) $this->views = $this->createViewList();

        return $this->views;
    }

    /**
     * Create select query
     *
     * @param string[]|Sql\Expression[] $columns
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

    abstract public function createConnection(): Connection;

    /**
     * @return TableList
     */
    protected function createTableList(): TableList {
        $cls = get_class($this) . '\TableList';

        return new $cls($this);
    }

    /**
     * @return ViewList
     */
    protected function createViewList(): ViewList {
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
