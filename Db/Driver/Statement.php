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
 * Interface Statement
 *
 * @package VV\Db\Driver
 */
interface Statement {

    /**
     * @param int $size
     *
     * @return void
     */
    public function setFetchSize(int $size): void;

    /**
     * @param array $params
     *
     * @return void
     */
    public function bind(array $params): void;

    /**
     * @return Result
     */
    public function exec(): Result;

    /**
     * @return void
     */
    public function close(): void;
}
