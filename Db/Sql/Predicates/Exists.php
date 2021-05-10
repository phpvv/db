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

use VV\Db\Sql\SelectQuery;

/**
 * Class Exists
 *
 * @package VV\Db\Sql\Predicate
 */
class Exists extends Base {

    private SelectQuery $query;

    /**
     * IsNull constructor.
     *
     * @param SelectQuery $query
     * @param bool        $not
     */
    public function __construct(SelectQuery $query, bool $not = false) {
        $this->query = $query;
        $this->not = $not;
    }

    public function query(): SelectQuery {
        return $this->query;
    }
}
