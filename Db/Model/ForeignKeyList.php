<?php declare(strict_types=1);

/*
 * This file is part of the VV package.
 *
 * (c) Volodymyr Sarnytskyi <v00v4n@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
namespace VV\Db\Model;

/**
 * Class FkList
 *
 * @package VV\Db\Model
 */
class ForeignKeyList implements \IteratorAggregate {

    /** @var ForeignKey[] */
    private array $foreignKeys = [];

    /** @var string[] */
    private array $names = [];

    public function __construct(array $foreignKeysData) {
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
    public function get(string $name): ?ForeignKey {
        return $this->foreignKeys[$name] ?? null;
    }

    /**
     * Get ForeignKey object by fields names
     *
     * @param string[] $fieldsNames
     *
     * @return ForeignKey| null
     */
    public function fromFields(array $fieldsNames): ?ForeignKey {
        $cnt = count($fieldsNames);
        foreach ($this->foreignKeys as $fk) {
            $fromField = $fk->fromField();

            if (count($fromField) != $cnt) continue;
            if ($cnt == 1) {
                if ($fromField === $fieldsNames) {
                    return $fk;
                }
            } elseif (!array_diff($fromField, $fieldsNames)) {
                return $fk;
            }
        }

        return null;
    }

    /**
     * @return string[]
     */
    public function names(): ?array {
        return $this->names;
    }

    /**
     * @inheritDoc
     * @return ForeignKey[]
     */
    public function getIterator() {
        foreach ($this->foreignKeys as $k => $v) {
            yield $k => $v;
        }
    }
}
