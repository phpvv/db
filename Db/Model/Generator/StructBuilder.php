<?php declare(strict_types=1);

/*
 * This file is part of the VV package.
 *
 * (c) Volodymyr Sarnytskyi <v00v4n@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
namespace VV\Db\Model\Generator;

/**
 * Interface InfoBuilder
 *
 * @package VV\Db\ModelGenerator
 */
interface StructBuilder {

    /**
     * @param \VV\Db\Connection $connection
     *
     * @return ObjectInfo[]
     */
    public function objectIterator(\VV\Db\Connection $connection): iterable;
}
