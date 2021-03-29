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
 * Class FieldList
 *
 * @package VV\Db\Model
 */
class FieldList implements \IteratorAggregate {

    /** @var Field[] */
    private array $fields = [];

    /** @var string[] */
    private array $names = [];

    public function __construct(array $fieldsData) {
        foreach ($fieldsData as $name => $data) {
            $this->names[] = $name;
            $this->fields[$name] = new Field($name, $data);
        }
    }

    /**
     * Get field object by field name
     *
     * @param string $name
     *
     * @return Field| null
     */
    public function get(string $name): ?Field {
        return $this->fields[$name] ?? null;
    }

    /**
     * @return string[]
     */
    public function names(): ?array {
        return $this->names;
    }

    /**
     * @inheritDoc
     * @return Field[]
     */
    public function getIterator() {
        foreach ($this->fields as $k => $v) {
            yield $k => $v;
        }
    }
}
