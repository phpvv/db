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

use VV\Db\Sql\Clauses\CombiningClauseItem as Item;
use VV\Db\Sql\SelectQuery;

class CombiningClause implements Clause
{
    public const CONN_UNION = 'UNION',
        CONN_INTERSECT = 'INTERSECT',
        CONN_EXCEPT = 'EXCEPT';

    /** @var Item[]  */
    private array $items = [];

    public function add(string $connector, bool $all, SelectQuery ...$queries): static
    {
        foreach ($queries as $query) {
            $this->items[] = $this->creteItem($connector, $all, $query);
        }

        return $this;
    }

    /**
     * @return Item[]
     */
    public function getItems(): array
    {
        return $this->items;
    }

    protected function creteItem(string $connector, bool $all, SelectQuery $query): Item
    {
        return new Item($connector, $all, $query);
    }

    public function isEmpty(): bool
    {
        return !$this->items;
    }
}
