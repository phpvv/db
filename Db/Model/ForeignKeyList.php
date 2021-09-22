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

/**
 * Class FkList
 *
 * @package VV\Db\Model
 */
class ForeignKeyList implements \IteratorAggregate
{
    /** @var ForeignKey[] */
    private array $foreignKeys = [];
    /** @var string[] */
    private array $names = [];

    public function __construct(array $foreignKeysData)
    {
        foreach ($foreignKeysData as $name => $data) {
            $this->names[] = $name;
            $this->foreignKeys[$name] = new ForeignKey($name, $data);
        }
    }

    /**
     * Get ForeignKey object by name
     *
     * @param string $name
     *
     * @return ForeignKey| null
     */
    public function get(string $name): ?ForeignKey
    {
        return $this->foreignKeys[$name] ?? null;
    }

    /**
     * Get ForeignKey object by column names
     *
     * @param string[] $columnNames
     *
     * @return ForeignKey| null
     */
    public function getFromColumns(array $columnNames): ?ForeignKey
    {
        $cnt = count($columnNames);
        foreach ($this->foreignKeys as $fk) {
            $fromColumns = $fk->getFromColumns();

            if (count($fromColumns) != $cnt) {
                continue;
            }
            if ($cnt == 1) {
                if ($fromColumns === $columnNames) {
                    return $fk;
                }
            } elseif (!array_diff($fromColumns, $columnNames)) {
                return $fk;
            }
        }

        return null;
    }

    /**
     * @return string[]|null
     */
    public function getNames(): ?array
    {
        return $this->names;
    }

    /**
     * @inheritDoc
     * @return ForeignKey[]
     */
    public function getIterator(): iterable
    {
        foreach ($this->foreignKeys as $k => $v) {
            yield $k => $v;
        }
    }
}
