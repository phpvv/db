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
 * Interface Result
 *
 * @package VV\Db\Driver
 */
interface Result {

    /**
     * Returns fetch data function according $flags
     *
     * @param int $flags Bit mask with self::FETCH_ constants
     *
     * @return \Traversable
     */
    public function fetchIterator(int $flags): \Traversable;

    /**
     * @return int|string|null
     */
    public function insertedId(): int|string|null;

    /**
     * Returns number of rows affected during statement execution
     *
     * @return int
     */
    public function affectedRows(): int;

    /**
     * Closes statement
     */
    public function close(): void;
}
