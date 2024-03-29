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

namespace VV\Db\Sql\Clauses;

/**
 * Class ItemList
 *
 * @package VV\Db\Sql\Clauses
 */
abstract class ItemList implements Clause
{
    protected array $items = [];

    /**
     * @return bool
     */
    public function isEmpty(): bool
    {
        return !$this->items;
    }

    /**
     * @return array
     */
    public function getItems(): array
    {
        return $this->items;
    }

    /**
     * @return $this
     */
    public function clear(): static
    {
        $this->items = [];

        return $this;
    }

    protected function appendItems(...$newItems): static
    {
        $this->items = array_merge($this->items, $newItems);

        return $this;
    }

    /**
     * Returns true if $object is instance of at least one of $classes
     */
    protected static function instanceOf(object $object, string ...$classes): bool
    {
        foreach ($classes as $class) {
            if (is_a($object, $class)) {
                return true;
            }
        }

        return false;
    }
}
