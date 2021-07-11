<?php declare(strict_types=1);

/*
 * This file is part of the VV package.
 *
 * (c) Volodymyr Sarnytskyi <v00v4n@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
namespace VV\Db\Sql\Predicates;

/**
 * Interface Predicate
 *
 * @package VV\Db\Sql
 */
interface Predicate
{

    /**
     * @return bool
     */
    public function isNegative(): bool;
}
