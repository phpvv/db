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

namespace VV\Db;

use VV\Db\Driver\QueryInfo;
use VV\Db\Exceptions\ConnectionError;
use VV\Db\Exceptions\ConnectionIsBusy;
use VV\Db\Sql\Expressions\Expression;
use VV\Db\Sql\Query;
use VV\Db\Sql\Stringifiers;

/**
 * Class Connection
 *
 * @package VV\Db
 */
final class Connection
{
    private Driver\Driver $driver;
    private ?string $host = null;
    private ?string $user = null;
    private ?string $password = null;
    private ?string $scheme = null;
    private ?string $charset = null;

    private bool $autoConnect = true;
    private ?Driver\Connection $driverConnection = null;
    private ?ConnectionError $connectionError = null;
    private ?Transaction $transaction = null;
    private bool $underExecution = false;
    private ?Stringifiers\Factory $sqlStringifiersFactory = null;

    /**
     * Constructor
     *
     * @param Driver\Driver $driver
     * @param string|null   $host
     * @param string|null   $user
     * @param string|null   $passwd
     * @param string|null   $scheme
     * @param string|null   $charset
     */
    public function __construct(
        Driver\Driver $driver,
        string $host = null,
        string $user = null,
        string $passwd = null,
        string $scheme = null,
        string $charset = null
    ) {
        $this->driver = $driver;
        if ($charset !== null) {
            $this->setCharset($charset);
        }
        if ($scheme !== null) {
            $this->setScheme($scheme);
        }
        if ($passwd !== null) {
            $this->setPassword($passwd);
        }
        if ($user !== null) {
            $this->setUser($user);
        }
        if ($host !== null) {
            $this->setHost($host);
        }
    }

    public function __wakeup()
    {
        $this->connectionError = null;
        $this->driverConnection = null;
        $this->connect();
    }

    public function isSame(self $connection): bool
    {
        return $this === $connection || $this->getHash() == $connection->getHash();
    }

    /**
     * @return $this
     */
    public function connect(): self
    {
        $this->throwIfConnected();

        if ($this->connectionError) {
            throw new ConnectionError('Connection Error found for instance', null, $this->connectionError);
        }

        if (!$this->getHost()) {
            throw new \LogicException('Host is not set');
        }

        try {
            $this->driverConnection = $this->driver->connect(
                $this->getHost(),
                $this->getUser(),
                $this->getPassword(),
                $this->getScheme(),
                $this->getCharset()
            );
        } catch (\Exception $e) {
            if (!$e instanceof ConnectionError) {
                $e = new ConnectionError('Connection Error', previous: $e);
            }

            $this->connectionError = $e;

            throw $e;
        }

        $this->transaction = null;

        return $this;
    }

    /**
     * @return $this
     */
    public function disconnect(): self
    {
        $this->throwIfNoConnection();

        $this->driverConnection->disconnect();
        $this->driverConnection = null;

        return $this;
    }

    /**
     * @return $this
     */
    public function reconnect(): self
    {
        if ($this->isConnected()) {
            $this->disconnect();
        }

        return $this->connect();
    }

    /**
     * @param Query|string $query
     * @param array|null   $params
     * @param int|null     $fetchSize
     *
     * @return Statement
     */
    public function prepare(Query|string $query, array $params = null, int $fetchSize = null): Statement
    {
        $this->tryAutoConnect();
        $this->throwIfConnectionError();

        $resultColumnsMap = null;
        if ($query instanceof Query) {
            $params = [];
            if ($query instanceof Sql\SelectQuery) {
                $resultColumnsMap = $query->getResultColumnsMap();
            }

            $query = $this->stringifyQuery($query, $params);
        }

        $queryInfo = new QueryInfo($query, $resultColumnsMap);

        $driverPrepared = $this->driverConnection->prepare($queryInfo);
        $prepared = new Statement($driverPrepared, $this, $this->driverConnection, $queryInfo);
        if ($params) {
            $prepared->bind($params);
        }
        if ($fetchSize !== null) {
            $prepared->setFetchSize($fetchSize);
        }

        return $prepared;
    }

    /**
     * Executes query and returns query statement
     *
     * @param string|Query $query
     * @param array|null   $params
     * @param int|null     $flags Default flags for fetch
     * @param null         $decorator
     * @param int|null     $fetchSize
     *
     * @return Result
     */
    public function query(
        Query|string $query,
        array $params = null,
        int $flags = null,
        $decorator = null,
        int $fetchSize = null
    ): Result {
        $this->throwIfConnectionError();

        $reconnected = false;
        while (true) {
            try {
                $prepared = $this->prepare($query);

                if ($params) {
                    if (is_string($params) || is_numeric($params)) {
                        $params = [$params];
                    }
                    $prepared->bind($params);
                }
                if ($fetchSize !== null) {
                    $prepared->setFetchSize($fetchSize);
                }

                $result = $prepared->exec();
                break;
            } catch (ConnectionError $e) {
                if ($reconnected) {
                    throw $e;
                }
                $reconnected = true;
                $this->reconnect();
            }
        }

        return $result
            ->setFlags($flags)
            ->setDecorator($decorator)
            ->setAutoClose(true);
    }

    /**
     * @return bool
     */
    public function isInTransaction(): bool
    {
        return $this->transaction && !$this->transaction->isFinished();
    }

    /**
     * @return Transaction|null
     * @internal
     */
    public function getTransaction(): ?Transaction
    {
        return $this->transaction;
    }

    /**
     * @return Transaction
     */
    public function startTransaction(): Transaction
    {
        $this->tryAutoConnect();

        // disallow to implicit start sub transaction
        if ($this->isInTransaction()) {
            throw new \LogicException('Transaction already started for this Connection.');
        }

        $this->transaction = new Transaction($this, $this->driverConnection);

        return $this->transaction->start();
    }

    /**
     * @return $this
     */
    public function commit(): self
    {
        $this->throwIfNoConnection();
        if (!$this->transaction) {
            throw new \LogicException('Nothing to commit');
        }

        $this->transaction = null;
        $this->driverConnection->commit();

        return $this;
    }

    /**
     * @return $this
     */
    public function rollback(): self
    {
        if (!$this->transaction) {
            return $this;
        }

        $this->throwIfNoConnection();
        $this->transaction = null;
        $this->driverConnection->rollback();

        return $this;
    }

    /**
     * @return bool
     */
    public function isUnderExecution(): bool
    {
        return $this->underExecution;
    }

    /**
     * Marks connection busy
     *
     * @return $this
     */
    public function acqureExecution(): self
    {
        if ($this->underExecution) {
            throw new ConnectionIsBusy();
        }

        // set stub for accidentally parallel execution
        $this->underExecution = true;

        return $this;
    }

    /**
     * Unmarks connection busy
     *
     * @return $this
     */
    public function releaseExecution(): self
    {
        // removes stub for accidentally parallel execution
        $this->underExecution = false;

        return $this;
    }

    /**
     * @return string|null
     */
    public function getHost(): ?string
    {
        return $this->host;
    }

    /**
     * @param string|null $host
     *
     * @return $this
     */
    public function setHost(?string $host): self
    {
        $this->throwIfConnected();
        $this->host = $host;

        return $this;
    }

    /**
     * @return string|null
     */
    public function getUser(): ?string
    {
        return $this->user;
    }

    /**
     * @param string|null $user
     *
     * @return $this
     */
    public function setUser(?string $user): self
    {
        $this->throwIfConnected();
        $this->user = $user;

        return $this;
    }

    /**
     * @param string|null $password
     *
     * @return $this
     */
    public function setPassword(?string $password): self
    {
        $this->throwIfConnected();
        $this->password = $password;

        return $this;
    }

    /**
     * @return string|null
     */
    public function getScheme(): ?string
    {
        if ($this->scheme === null) {
            return $this->getUser();
        }

        return $this->scheme;
    }

    /**
     * @param string|null $scheme
     *
     * @return $this
     */
    public function setScheme(?string $scheme): self
    {
        $this->throwIfConnected();
        $this->scheme = $scheme;

        return $this;
    }

    /**
     * @return string|null
     */
    public function getCharset(): ?string
    {
        return $this->charset;
    }

    /**
     * @param string|null $charset
     *
     * @return $this
     */
    public function setCharset(?string $charset): self
    {
        $this->throwIfConnected();
        $this->charset = $charset;

        return $this;
    }

    /**
     * @return bool
     */
    public function isAutoConnect(): bool
    {
        return $this->autoConnect;
    }

    /**
     * @param bool $autoConnect
     *
     * @return $this
     */
    public function setAutoConnect(bool $autoConnect): self
    {
        $this->autoConnect = $autoConnect;

        return $this;
    }

    /**
     * @return bool
     */
    public function isMysql(): bool
    {
        return $this->getDbmsName() == Driver\Driver::DBMS_MYSQL;
    }

    /**
     * @return bool
     */
    public function isPostgresql(): bool
    {
        return $this->getDbmsName() == Driver\Driver::DBMS_POSTGRES;
    }

    /**
     * @return bool
     */
    public function isOracle(): bool
    {
        return $this->getDbmsName() == Driver\Driver::DBMS_ORACLE;
    }

    /**
     * @return bool
     */
    public function isMssql(): bool
    {
        return $this->getDbmsName() == Driver\Driver::DBMS_MSSQL;
    }

    /**
     * @return bool
     */
    public function isConnected(): bool
    {
        return !empty($this->driverConnection);
    }

    /**
     * @return bool
     */
    public function isBusy(): bool
    {
        return $this->isInTransaction() || $this->isUnderExecution();
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
        return (new Sql\SelectQuery($this))->columns(...$columns);
    }

    /**
     * Create insert query
     *
     * @param array|null $data
     *
     * @return Sql\InsertQuery
     */
    public function insert(array $data = null): Sql\InsertQuery
    {
        $insert = (new Sql\InsertQuery($this));
        if ($data) {
            $insert->set($data);
        }

        return $insert;
    }

    /**
     * Create update query
     *
     * @param array|null $data
     *
     * @return Sql\UpdateQuery
     */
    public function update(array $data = null): Sql\UpdateQuery
    {
        $update = (new Sql\UpdateQuery($this));
        if ($data) {
            $update->set($data);
        }

        return $update;
    }

    /**
     * Create delete query
     *
     * @return Sql\DeleteQuery
     */
    public function delete(): Sql\DeleteQuery
    {
        return (new Sql\DeleteQuery($this));
    }

    /**
     * @return ConnectionError|null
     */
    public function getConnectionError(): ?ConnectionError
    {
        return $this->connectionError;
    }

    /**
     * @return string
     */
    public function getDbmsName(): string
    {
        return $this->driver->getDbmsName();
    }

    /**
     * @param Query $query
     * @param       $params
     *
     * @return string
     */
    public function stringifyQuery(Query $query, &$params): string
    {
        $stringifier = $this->getStringifierForQuery($query);
        if (!$stringifier) {
            throw new \InvalidArgumentException('Unknown query type');
        }

        $nonEmptyClausesIds = $query->getNonEmptyClausesIds();
        $supportedClausesIds = $stringifier->getSupportedClausesIds();

        $intersection = $nonEmptyClausesIds | $supportedClausesIds;
        if ($intersection != $supportedClausesIds) {
            throw new \RuntimeException('Not all present clauses are supported by DB driver');
        }

        return $stringifier->stringify($params);
    }

    /**
     * @return $this
     */
    public function throwIfConnectionError(): self
    {
        if ($e = $this->getConnectionError()) {
            throw $e;
        }

        return $this;
    }

    /**
     * @return string|null
     */
    protected function getPassword(): ?string
    {
        return $this->password;
    }

    /**
     * @return $this
     */
    protected function tryAutoConnect(): self
    {
        if (!$this->isConnected()) {
            if (!$this->isAutoConnect()) {
                throw new \LogicException('No Connection');
            }

            $this->connect();
        }

        return $this;
    }

    /**
     * @return $this
     */
    protected function throwIfNoConnection(): self
    {
        if (!$this->isConnected()) {
            throw new \LogicException('No Connection');
        }

        return $this;
    }

    /**
     * @return $this
     */
    protected function throwIfConnected(): self
    {
        if ($this->isConnected()) {
            throw new \LogicException('Already connected');
        }

        return $this;
    }

    /**
     * @param Query $query
     *
     * @return Stringifiers\QueryStringifier
     */
    private function getStringifierForQuery(Query $query): Stringifiers\QueryStringifier
    {
        $factory = $this->getSqlStringifiersFactory();

        return match (true) {
            $query instanceof Sql\SelectQuery => $factory->createSelectStringifier($query),
            $query instanceof Sql\InsertQuery => $factory->createInsertStringifier($query),
            $query instanceof Sql\UpdateQuery => $factory->createUpdateStringifier($query),
            $query instanceof Sql\DeleteQuery => $factory->createDeleteStringifier($query),
            default => throw new \LogicException('Unknown query type'),
        };
    }

    private function getSqlStringifiersFactory(): Stringifiers\Factory
    {
        $factory = &$this->sqlStringifiersFactory;
        if (!$factory) {
            $factory = $this->driver->getSqlStringifiersFactory();
        }
        if (!$factory) {
            $factory = match ($this->driver->getDbmsName()) {
                Driver\Driver::DBMS_MYSQL => new Stringifiers\Mysql\Factory(),
                Driver\Driver::DBMS_ORACLE => new Stringifiers\Oracle\Factory(),
                Driver\Driver::DBMS_POSTGRES => new Stringifiers\Postgres\Factory(),
            };
        }

        return $factory;
    }

    private function getHash(): string
    {
        return md5(join(
            ';',
            [
                get_class($this->driver),
                $this->charset,
                $this->scheme,
                $this->password,
                $this->user,
                $this->host,
            ]
        ));
    }
}
