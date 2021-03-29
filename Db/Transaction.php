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

/**
 * Class Transaction
 *
 * @package VV\Db
 */
final class Transaction {

    private ?Connection $connection;

    private ?Driver\Connection $driverConnection;

    private bool $started = false;

    /**
     * Transaction constructor.
     *
     * @param Connection        $connection
     * @param Driver\Connection $driverConnection
     */
    public function __construct(Connection $connection, Driver\Connection $driverConnection) {
        $this->connection = $connection;
        $this->driverConnection = $driverConnection;
    }

    /**
     * @return Connection
     */
    public function connection(): Connection {
        return $this->connection;
    }

    public function start(): self {
        if ($this->started) throw new \LogicException('Transaction already started');
        $this->throwIfFinished();

        if ($this->connection->transaction() !== $this) {
            throw new \LogicException('Improperly created Transaction. Please use Connection::startTransaction()');
        }

        $this->driverConnection->startTransaction();
        $this->started = true;

        return $this;
    }

    /**
     * @return $this
     */
    public function commit(): self {
        return $this->finish(true);
    }

    /**
     * @return $this
     */
    public function rollback(): self {
        return $this->finish(false);
    }

    /**
     * @return bool
     */
    public function isFinished(): bool {
        return !$this->connection;
    }

    /**
     * @param bool $commit
     *
     * @return $this
     */
    private function finish(bool $commit): self {
        if (!$this->started) throw new \LogicException('Transaction not started');
        $this->throwIfFinished();

        if ($commit) {
            $this->driverConnection->commit();
        } else {
            $this->driverConnection->rollback();
        }

        $this->connection = $this->driverConnection = null;

        return $this;
    }

    /**
     * @return $this
     */
    private function throwIfFinished(): self {
        if (!$this->connection) {
            throw new \LogicException('Transaction is already finished');
        }

        return $this;
    }
}
