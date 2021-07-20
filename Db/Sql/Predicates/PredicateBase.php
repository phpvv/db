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
 * Class PredicateBase
 *
 * @package VV\Db\Sql\Predicates
 */
abstract class PredicateBase implements Predicate
{
    private bool $not;

    /**
     * PredicateBase constructor.
     *
     * @param bool $not
     */
    protected function __construct(bool $not)
    {
        $this->not = $not;
    }

    /**
     * @inheritDoc
     */
    public function isNegative(): bool
    {
        return $this->not;
    }
}
