<?php

/*
 * This file is part of the phpvv package.
 *
 * (c) Volodymyr Sarnytskyi <v00v4n@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace VV\Db\Sql\Clauses;

use VV\Db\Sql\SelectQuery;

class CombiningClauseItem
{
    public function __construct(private string $connector, private bool $all, private SelectQuery $query)
    {
    }

    public function getConnector(): string
    {
        return $this->connector;
    }

    public function isAll(): bool
    {
        return $this->all;
    }

    public function getQuery(): SelectQuery
    {
        return $this->query;
    }
}
