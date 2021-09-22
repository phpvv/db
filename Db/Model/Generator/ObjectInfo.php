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

namespace VV\Db\Model\Generator;

/**
 * Class DataObjectInfo
 *
 * @package VV\Db\ModelGenerator
 */
class ObjectInfo
{

    private string $name;
    private string $type;
    /** @var array[] */
    private array $columns = [];
    /** @var array[] */
    private array $foreignKeys = [];
    private string $typePlural;

    public function __construct(string $name, string $type, string $typePlural = null)
    {
        $this->name = $name;
        $this->type = $type;
        $this->typePlural = $typePlural ?: $type . 's';
    }

    /**
     * @return string
     */
    public function getName(): string
    {
        return $this->name;
    }

    /**
     * @return string
     */
    public function getType(): string
    {
        return $this->type;
    }

    /**
     * @return string
     */
    public function getTypePlural(): string
    {
        return $this->typePlural;
    }

    /**
     * @return array[]
     */
    public function getColumns(): array
    {
        return $this->columns;
    }

    /**
     * @return array[]
     */
    public function getForeignKeys(): array
    {
        return $this->foreignKeys;
    }

    public function addColumn(
        string $name,
        string $type,
        ?int $length,
        ?int $intSize,
        ?int $precision,
        ?int $scale,
        ?string $default,
        bool $notnull,
        bool $unsigned,
        bool $inPk
    ) {
        $this->columns[$name] = [
            $type,
            $length,
            $intSize,
            $precision,
            $scale,
            $default,
            $notnull,
            $unsigned,
            'pk' => $inPk,
        ];
    }

    public function addForeignKey(string $name, array $fromColumns, string $toTable, array $toColumns)
    {
        $this->foreignKeys[$name] = [$fromColumns, $toTable, $toColumns];
    }
}
