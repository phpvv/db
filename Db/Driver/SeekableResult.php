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

namespace VV\Db\Driver;

/**
 * Interface Seekable
 *
 * @package VV\Db\Result
 */
interface SeekableResult
{
    /**
     * Seeks to an arbitrary row
     *
     * @param int $offset
     */
    public function seek(int $offset);
}
