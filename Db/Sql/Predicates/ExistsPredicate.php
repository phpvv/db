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
 * Class ExistsPredicate
 *
 * @package VV\Db\Sql\Predicates
 */
class ExistsPredicate extends PredicateBase
{
    private SelectQuery $query;

    /**
     * ExistsPredicate constructor.
     *
     * @param SelectQuery $query
     * @param bool        $not
     */
    public function __construct(SelectQuery $query, bool $not = false)
    {
        parent::__construct($not);

        $this->query = $query;
    }

    public function getQuery(): SelectQuery
    {
        return $this->query;
    }
}
