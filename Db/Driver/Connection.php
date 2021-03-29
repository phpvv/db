<?php declare(strict_types=1);

/*
 * This file is part of the VV package.
 *
 * (c) Volodymyr Sarnytskyi <v00v4n@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
namespace VV\Db\Driver;

/**
 * Interface Connection
 *
 * @package VV\Db\Driver
 */
interface Connection {

    /**
     * @param \VV\Db\Driver\QueryInfo $query
     *
     * @return Statement
     */
    public function prepare(\VV\Db\Driver\QueryInfo $query): Statement;

    /**
     * @return void
     */
    public function startTransaction(): void;

    /**
     * @param bool $autocommit
     *
     * @return void
     */
    public function commit(bool $autocommit = false): void;

    /**
     * @return void
     */
    public function rollback(): void;

    /**
     * @return void
     */
    public function disconnect(): void;
}
