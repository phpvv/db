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
 * Class Statement
 *
 * @package VV\Db
 */
final class Statement {

    const DATETIME_FORMAT = 'Y-m-d H:i:s';

    private ?Driver\Statement $driver;

    private Connection $connection;

    private Driver\Connection $driverConnection;

    private Driver\QueryInfo $queryInfo;

    private ?int $fetchSize = null;

    /**
     * Prepared constructor.
     *
     * @param Driver\Statement  $driver
     * @param Connection        $connection
     * @param Driver\Connection $driverConnection
     * @param Driver\QueryInfo  $queryInfo
     */
    public function __construct(Driver\Statement $driver, Connection $connection, Driver\Connection $driverConnection, Driver\QueryInfo $queryInfo) {
        $this->driver = $driver;
        $this->connection = $connection;
        $this->driverConnection = $driverConnection;
        $this->queryInfo = $queryInfo;
    }

    /**
     * @return Driver\QueryInfo
     */
    public function queryInfo(): Driver\QueryInfo {
        return $this->queryInfo;
    }

    /**
     * @return int|null
     */
    public function fetchSize(): ?int {
        return $this->fetchSize;
    }

    /**
     * @param int $fetchSize
     *
     * @return $this|Statement
     */
    public function setFetchSize(int $fetchSize): self {
        $this->driverOrThrow()->setFetchSize($this->fetchSize = $fetchSize);

        return $this;
    }

    /**
     * @param array $params
     *
     * @return $this|Statement
     */
    public function bind(array $params): self {
        $this->castParamList($params);
        $this->driverOrThrow()->bind($params);

        return $this;
    }

    /**
     * @return Result
     */
    public function exec(): Result {
        $connection = $this->connection;
        // enable fatal error stub (paralel execution)
        $connection->acqureExecution();
        try {
            $driverResult = $this->driverOrThrow()->exec();
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
    public function result(): Result {
        return $this->exec();
    }

    /**
     * @return $this|Statement
     */
    public function close(): self {
        if (!$this->isClosed()) {
            $this->driver->close();
            $this->driver = null;
        }

        return $this;
    }

    /**
     * @return boolean
     */
    public function isClosed(): bool {
        return empty($this->driver);
    }

    /**
     * @return Driver\Statement
     */
    protected function driverOrThrow(): Driver\Statement {
        if (!$this->driver) throw new \LogicException('Prepared Query is already closed');

        return $this->driver;
    }

    /**
     * @param $params
     */
    protected function castParamList(&$params): void {
        foreach ($params as &$param) {
            if ($param instanceof Param) {
                $param->setValue($this->castParam($param->value()));
            } else {
                $param = $this->castParam($param);
            }
        }
    }

    /**
     * @param mixed $param
     *
     * @return mixed
     */
    protected function castParam(mixed $param): mixed {
        return match (true) {
            $param instanceof \DateTimeInterface => $this->formatDateTime($param),
            default => $param,
        };
    }

    /**
     * @param \DateTimeInterface $datetime
     *
     * @return string
     */
    protected function formatDateTime(\DateTimeInterface $datetime): string {
        return $datetime->format(self::DATETIME_FORMAT);
    }
}
