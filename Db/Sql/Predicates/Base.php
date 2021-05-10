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
 * Class Base
 *
 * @package VV\Db\Sql\Predicate
 */
abstract class Base implements \VV\Db\Sql\Predicates\Predicate {

    protected bool $not = false;

    /**
     * @return bool
     */
    public function isNegative(): bool {
        return $this->not;
    }
}
