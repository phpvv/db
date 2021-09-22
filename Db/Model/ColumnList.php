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

namespace VV\Db\Model;

class ColumnList implements \IteratorAggregate
{
    /** @var Column[] */
    private array $columns = [];
    /** @var string[] */
    private array $names = [];

    public function __construct(array $columnsData)
    {
        foreach ($columnsData as $name => $data) {
            $this->names[] = $name;
            $this->columns[$name] = new Column($name, $data);
        }
    }

    /**
     * Returns Column by name
     */
    public function get(string $name): ?Column
    {
        return $this->columns[$name] ?? null;
    }

    /**
     * Returns array of names of all columns
     */
    public function getNames(): ?array
    {
        return $this->names;
    }

    /**
     * @inheritDoc
     * @return Column[]
     */
    public function getIterator(): iterable
    {
        foreach ($this->columns as $k => $v) {
            yield $k => $v;
        }
    }
}
