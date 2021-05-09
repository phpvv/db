<?php declare(strict_types=1);

/*
 * This file is part of the VV package.
 *
 * (c) Volodymyr Sarnytskyi <v00v4n@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
namespace VV\Db;

use JetBrains\PhpStorm\Pure;
use VV\Db\Exceptions\ConnectionError;
use VV\Db\Sql\Query;
use VV\Db\Sql\Stringifiers\Factory as SqlStringifiersFactory;
use VV\Db\Sql\Stringifiers\QueryStringifier;

/**
 * Class Connection
 *
 * @package VV\Db
 */
final class Connection {

    private Driver\Driver $driver;
    private ?string $host = null;
    private ?string $user = null;
    private ?string $passwd = null;
    private ?string $scheme = null;
    private ?string $charset = null;

    private bool $autoConnect = true;
    private ?Driver\Connection $driverConnection = null;
    private ?ConnectionError $connectionError = null;
    private ?Transaction $transaction = null;
    private bool $underExecution = false;
    private ?SqlStringifiersFactory $sqlStringifiersFactory = null;

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
    public function __construct(Driver\Driver $driver, string $host = null, string $user = null, string $passwd = null, string $scheme = null, string $charset = null) {
        $this->driver = $driver;
        if ($charset !== null) $this->setCharset($charset);
        if ($scheme !== null) $this->setScheme($scheme);
        if ($passwd !== null) $this->setPasswd($passwd);
        if ($user !== null) $this->setUser($user);
        if ($host !== null) $this->setHost($host);
    }

    public function __wakeup() {
        $this->connectionError = null;
        $this->driverConnection = null;
        $this->connect();
    }

    public function isSame(self $connection): bool {
        return $this === $connection || $this->hash() == $connection->hash();
    }

    /**
     * @return $this
     */
    public function connect(): self {
        $this->throwIfConnected();

        if ($this->connectionError) {
            throw new ConnectionError('Connection Error found for instance', null, $this->connectionError);
        }

        if (!$this->host()) throw new \LogicException('Host is not set');

        try {
            $this->driverConnection = $this->driver->connect(
                $this->host(),
                $this->user(),
                $this->passwd(),
                $this->scheme(),
                $this->charset()
            );
        } catch (\Exception $e) {
            if (!$e instanceof ConnectionError) {
                $e = new ConnectionError(null, null, $e);
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
    public function disconnect(): self {
        $this->throwIfNoConnection();

        $this->driverConnection->disconnect();
        $this->driverConnection = null;

        return $this;
    }

    /**
     * @return $this
     */
    public function reconnect(): self {
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
    public function prepare(Query|string $query, array $params = null, int $fetchSize = null): Statement {
        $this->tryAutoConnect();

        $resultFieldsMap = null;
        if ($query instanceof Query) {
            $params = [];
            if ($query instanceof Sql\SelectQuery) {
                $resultFieldsMap = $query->resultFieldsMap();
            }

            $query = $this->stringifyQuery($query, $params);
        }

        $queryInfo = new \VV\Db\Driver\QueryInfo($query, $resultFieldsMap);

        $driverPrepared = $this->driverConnection->prepare($queryInfo);
        $prepared = new Statement($driverPrepared, $this, $this->driverConnection, $queryInfo);
        if ($params) $prepared->bind($params);
        if ($fetchSize !== null) $prepared->setFetchSize($fetchSize);

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
    public function query(Query|string $query, array $params = null, int $flags = null, $decorator = null, int $fetchSize = null): Result {
        $prepared = $this->prepare($query);

        if ($params) {
            if (is_string($params) || is_numeric($params)) $params = [$params];
            $prepared->bind($params);
        }
        if ($fetchSize !== null) $prepared->setFetchSize($fetchSize);

        return $prepared->exec()
            ->setFlags($flags)
            ->setDecorator($decorator)
            ->setAutoClose(true);
    }

    /**
     * @return bool
     */
    #[Pure]
    public function isInTransaction(): bool {
        return $this->transaction && !$this->transaction->isFinished();
    }

    /**
     * @return Transaction|null
     * @internal
     */
    public function transaction(): ?Transaction {
        return $this->transaction;
    }

    /**
     * @return Transaction
     */
    public function startTransaction(): Transaction {
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
    public function commit(): self {
        $this->throwIfNoConnection();
        if (!$this->transaction) throw new \LogicException('Nothing to commit');

        $this->transaction = null;
        $this->driverConnection->commit();

        return $this;
    }

    /**
     * @return $this
     */
    public function rollback(): self {
        if (!$this->transaction) return $this;

        $this->throwIfNoConnection();
        $this->transaction = null;
        $this->driverConnection->rollback();

        return $this;
    }

    /**
     * @return bool
     */
    public function isUnderExecution(): bool {
        return $this->underExecution;
    }

    /**
     * Marks connection busy
     *
     * @return $this
     */
    public function acqureExecution(): self {
        if ($this->underExecution) throw new \VV\Db\Exceptions\ConnectionIsBusy;

        // set stub for accidentaly paralel execution
        $this->underExecution = true;

        return $this;
    }

    /**
     * Unmarks connection busy
     *
     * @return $this
     */
    public function releaseExecution(): self {
        // removes stub for accidentaly paralel execution
        $this->underExecution = false;

        return $this;
    }

    /**
     * @return string|null
     */
    public function host(): ?string {
        return $this->host;
    }

    /**
     * @param string|null $host
     *
     * @return $this
     */
    public function setHost(?string $host): self {
        $this->throwIfConnected();
        $this->host = $host;

        return $this;
    }

    /**
     * @return string|null
     */
    public function user(): ?string {
        return $this->user;
    }

    /**
     * @param string|null $user
     *
     * @return $this
     */
    public function setUser(?string $user): self {
        $this->throwIfConnected();
        $this->user = $user;

        return $this;
    }

    /**
     * @param string|null $passwd
     *
     * @return $this
     */
    public function setPasswd(?string $passwd): self {
        $this->throwIfConnected();
        $this->passwd = $passwd;

        return $this;
    }

    /**
     * @return string|null
     */
    #[Pure]
    public function scheme(): ?string {
        if ($this->scheme === null) {
            return $this->user();
        }

        return $this->scheme;
    }

    /**
     * @param string|null $scheme
     *
     * @return $this
     */
    public function setScheme(?string $scheme): self {
        $this->throwIfConnected();
        $this->scheme = $scheme;

        return $this;
    }

    /**
     * @return string|null
     */
    public function charset(): ?string {
        return $this->charset;
    }

    /**
     * @param string|null $charset
     *
     * @return $this
     */
    public function setCharset(?string $charset): self {
        $this->throwIfConnected();
        $this->charset = $charset;

        return $this;
    }

    /**
     * @return bool
     */
    public function isAutoConnect(): bool {
        return $this->autoConnect;
    }

    /**
     * @param bool $autoConnect
     *
     * @return $this
     */
    public function setAutoConnect(bool $autoConnect): self {
        $this->autoConnect = $autoConnect;

        return $this;
    }

    /**
     * @return bool
     */
    public function isMysql(): bool {
        return $this->dbms() == Driver\Driver::DBMS_MYSQL;
    }

    /**
     * @return bool
     */
    public function isPostgresql(): bool {
        return $this->dbms() == Driver\Driver::DBMS_POSTGRES;
    }

    /**
     * @return bool
     */
    public function isOracle(): bool {
        return $this->dbms() == Driver\Driver::DBMS_ORACLE;
    }

    /**
     * @return bool
     */
    public function isMssql(): bool {
        return $this->dbms() == Driver\Driver::DBMS_MSSQL;
    }

    /**
     * @return bool
     */
    public function isConnected(): bool {
        return !empty($this->driverConnection);
    }

    /**
     * @return bool
     */
    public function isBusy(): bool {
        return $this->isInTransaction() || $this->isUnderExecution();
    }

    /**
     * Create select query
     *
     * @param string[]|\VV\Db\Sql\Expressions\Expression[] $columns
     *
     * @return Sql\SelectQuery
     */
    public function select(...$columns): Sql\SelectQuery {
        return (new Sql\SelectQuery($this))->columns(...$columns);
    }

    /**
     * Create insert query
     *
     * @param array|null $data
     *
     * @return Sql\InsertQuery
     */
    public function insert(array $data = null): Sql\InsertQuery {
        $insert = (new Sql\InsertQuery($this));
        if ($data) $insert->set($data);

        return $insert;
    }

    /**
     * Create update query
     *
     * @param array|null $data
     *
     * @return Sql\UpdateQuery
     */
    public function update(array $data = null): Sql\UpdateQuery {
        $update = (new Sql\UpdateQuery($this));
        if ($data) $update->set($data);

        return $update;
    }

    /**
     * Create delete query
     *
     * @return Sql\DeleteQuery
     */
    public function delete(): Sql\DeleteQuery {
        return (new Sql\DeleteQuery($this));
    }

    /**
     * @return ConnectionError|null
     */
    public function connectionError(): ?ConnectionError {
        return $this->connectionError;
    }

    /**
     * @return string
     */
    public function dbms(): string {
        return $this->driver->dbms();
    }

    /**
     * @param Query $query
     * @param       $params
     *
     * @return string
     */
    public function stringifyQuery(Query $query, &$params): string {
        $stringifier = $this->stringifierForQuery($query);
        if (!$stringifier) throw new \InvalidArgumentException('Unknown query type');

        $noemtClauses = $query->nonEmptyClausesIds();
        $suprtClauses = $stringifier->supportedClausesIds();

        $intersec = $noemtClauses | $suprtClauses;
        if ($intersec != $suprtClauses) {
            throw new \RuntimeException('Not all present clauses are supported by DB driver');
        }

        return $stringifier->stringify($params);
    }

    /**
     * @return string|null
     */
    protected function passwd(): ?string {
        return $this->passwd;
    }

    /**
     * @return $this
     */
    protected function tryAutoConnect(): self {
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
    protected function throwIfNoConnection(): self {
        if (!$this->isConnected()) {
            throw new \LogicException('No Connection');
        }

        return $this;
    }

    /**
     * @return $this
     */
    protected function throwIfConnected(): self {
        if ($this->isConnected()) throw new \LogicException('Already connected');

        return $this;
    }

    /**
     * @param Query $query
     *
     * @return QueryStringifier
     */
    private function stringifierForQuery(Query $query): QueryStringifier {
        $factory = $this->sqlStringifiersFactory();

        return match (true) {
            $query instanceof Sql\SelectQuery => $factory->createSelectStringifier($query),
            $query instanceof Sql\InsertQuery => $factory->createInsertStringifier($query),
            $query instanceof Sql\UpdateQuery => $factory->createUpdateStringifier($query),
            $query instanceof Sql\DeleteQuery => $factory->createDeleteStringifier($query),
            default => throw new \LogicException('Unknown query type'),
        };
    }

    private function sqlStringifiersFactory(): SqlStringifiersFactory {
        $factory = &$this->sqlStringifiersFactory;
        if (!$factory) $factory = $this->driver->sqlStringifiersFactory();
        if (!$factory) {
            $factory = match ($this->driver->dbms()) {
                Driver\Driver::DBMS_MYSQL => new \VV\Db\Sql\Stringifiers\Mysql\Factory,
                Driver\Driver::DBMS_ORACLE => new \VV\Db\Sql\Stringifiers\Oracle\Factory,
                Driver\Driver::DBMS_POSTGRES => new \VV\Db\Sql\Stringifiers\Postgres\Factory,
            };
        }

        return $factory;
    }

    private function hash(): string {
        return md5(join(';', [
            get_class($this->driver),
            $this->charset,
            $this->scheme,
            $this->passwd,
            $this->user,
            $this->host,
        ]));
    }
}
