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

/**
 * Class Statement
 *
 * @package VV\Db
 */
final class Statement
{
    private ?Driver\Statement $driver;
    private Connection $connection;
    private Driver\Connection $driverConnection;
    private Driver\QueryInfo $queryInfo;
    private ?int $fetchSize = null;

    /**
     * Prepared constructor.
     *
     * @param Driver\Statement $driver
     * @param Connection $connection
     * @param Driver\Connection $driverConnection
     * @param Driver\QueryInfo $queryInfo
     */
    public function __construct(
        Driver\Statement $driver,
        Connection $connection,
        Driver\Connection $driverConnection,
        Driver\QueryInfo $queryInfo
    ) {
        $this->driver = $driver;
        $this->connection = $connection;
        $this->driverConnection = $driverConnection;
        $this->queryInfo = $queryInfo;
    }

    /**
     * @return Driver\QueryInfo
     */
    public function getQueryInfo(): Driver\QueryInfo
    {
        return $this->queryInfo;
    }

    /**
     * @return int|null
     */
    public function getFetchSize(): ?int
    {
        return $this->fetchSize;
    }

    /**
     * @param int $fetchSize
     *
     * @return $this|Statement
     */
    public function setFetchSize(int $fetchSize): self
    {
        $this->getDriverOrThrow()->setFetchSize($this->fetchSize = $fetchSize);

        return $this;
    }

    /**
     * @param array $params
     *
     * @return $this|Statement
     */
    public function bind(array $params): self
    {
        $this->getDriverOrThrow()->bind($params);

        return $this;
    }

    /**
     * @return Result
     */
    public function exec(): Result
    {
        $connection = $this->connection;
        $connection->throwIfConnectionError();

        // enable fatal error stub (parallel execution)
        $connection->acqureExecution();
        try {
            $driverResult = $this->getDriverOrThrow()->exec();
            $result = new Result($driverResult, $this);

            // auto commit for modificatory queries if no transaction was started
            if ($this->queryInfo->isModificatory() && !$connection->isInTransaction()) {
                $this->driverConnection->commit(true);
            }

            return $result;
        } finally {
            $connection->releaseExecution();
        }
    }

    /**
     * Alias for exec
     *
     * @return Result
     */
    public function result(): Result
    {
        return $this->exec();
    }

    /**
     * @return $this|Statement
     */
    public function close(): self
    {
        if (!$this->isClosed()) {
            $this->driver->close();
            $this->driver = null;
        }

        return $this;
    }

    /**
     * @return bool
     */
    public function isClosed(): bool
    {
        return empty($this->driver);
    }

    /**
     * @return Driver\Statement
     */
    protected function getDriverOrThrow(): Driver\Statement
    {
        if (!$this->driver) {
            throw new \LogicException('Prepared Query is already closed');
        }

        return $this->driver;
    }
}
