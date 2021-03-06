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

namespace VV\Db\Sql\Predicates;

/**
 * Interface Predicate
 *
 * @package VV\Db\Sql\Predicates
 */
interface Predicate
{
    /**
     * @return bool
     */
    public function isNegative(): bool;
}
