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

use VV\Db\Sql\Stringifiers\Factory as StringifiersFactory;

/**
 * Interface Driver
 *
 * @package VV\Db
 */
interface Driver
{
    public const DBMS_MYSQL = 'mysql',
        DBMS_POSTGRES = 'postgres',
        DBMS_ORACLE = 'oracle',
        DBMS_MSSQL = 'mssql';

    /**
     * Creates connection
     *
     * @param string      $host
     * @param string      $user
     * @param string      $passwd
     * @param string|null $scheme
     * @param string|null $charset
     *
     * @return Connection
     */
    public function connect(string $host, string $user, string $passwd, ?string $scheme, ?string $charset): Connection;

    /**
     * Name of DBMS
     *
     * @return string
     */
    public function getDbmsName(): string;

    /**
     * Returns Sql Stringifiers Factory
     *
     * @return StringifiersFactory|null
     */
    public function getSqlStringifiersFactory(): ?StringifiersFactory;
}
