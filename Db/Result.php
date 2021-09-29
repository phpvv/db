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

namespace VV\Db;

use VV\Db;

/**
 * Class Result
 *
 * @property-read mixed      $cell
 * @property-read array      $row
 * @property-read array      $column
 * @property-read array      $rows
 * @property-read array      $assoc
 *
 * @property-read int|string $insertedId
 * @property-read int        $affectedRows Returns number of rows affected during statement execution
 */
final class Result implements \IteratorAggregate
{
    private ?Driver\Result $driver;
    private Statement $prepared;
    /** @var \Generator[] */
    private array $fetchGeneratorList = [];
    private ?\Closure $decorator = null;
    private int $flags = Db::FETCH_ASSOC;
    private bool $autoClose = false;
    private bool $used = false;
    private array|bool|null $resultColumnsMap = false;

    /**
     * Result constructor.
     *
     * @param Driver\Result $driver
     * @param Statement     $prepared
     */
    public function __construct(Driver\Result $driver, Statement $prepared)
    {
        $this->driver = $driver;
        $this->prepared = $prepared;
    }

    public function __get($var)
    {
        return match ($var) {
            'cell' => $this->cell(),
            'row' => $this->row(),
            'column' => $this->column(),
            'rows' => $this->rows(),
            'assoc' => $this->assoc(),
            'insertedId' => $this->insertedId(),
            'affectedRows' => $this->affectedRows(),
            default => throw new \LogicException("Undefined property $var"),
        };
    }

    /**
     * Sets default flags
     *
     * @param int|null $flags
     *
     * @return $this
     */
    public function setFlags(?int $flags): self
    {
        $this->flags = $flags ?? Db::FETCH_ASSOC;

        return $this;
    }

    /**
     * @param string|int|\Closure|null $decorator
     *
     * @return $this
     */
    public function setDecorator(string|int|\Closure|null $decorator): self
    {
        if (is_scalar($decorator)) {
            $decorator = function (&$row) use ($decorator) {
                $row = $row[$decorator];
            };
        }

        $this->decorator = $decorator ?: null;

        return $this;
    }

    /**
     * Returns row fetched from db
     *
     * @param int|null $flags One or more of VV\Db::FETCH_*
     *
     * @return array|null
     */
    public function fetch(int $flags = null): ?array
    {
        $driver = $this->getDriverOrThrow();

        $this->used = true;
        if ($flags === null) {
            $flags = $this->flags;
        }

        // get fetch iterator from driver
        $iterator = &$this->fetchGeneratorList[$flags];
        if (!$iterator) {
            $iterator = $driver->getIterator($flags);
        }

        $row = $iterator->current();
        if ($row) {
            $iterator->next();

            // make sub arrays
            if (is_array($row) && $resultColumnsMap = $this->getResultColumnsMap()) {
                self::acceptRowResultColumnsMap($row, $resultColumnsMap);
            }

            // decorate row
            if ($decorator = $this->decorator) {
                if ($decorator($row) === false) { // skip this row
                    $row = $this->fetch($flags);
                }
            }
        } elseif ($this->isAutoClose()) {
            $this->close();
        }

        return $row;
    }

    /**
     * Returns first (or $columnIndex) cell of first row
     *
     * @param int      $columnIndex Index of column whose cell is needed
     * @param int|null $flags       One or more of VV\Db::FETCH_*
     *
     * @return mixed
     */
    public function cell(int $columnIndex = 0, int $flags = null): mixed
    {
        $row = $this->row($flags | Db::FETCH_NUM);
        if (!$row) {
            return null;
        }

        return $row[$columnIndex];
    }

    /**
     * Returns first row
     *
     * @param int|null $flags One or more of VV\Db::FETCH_*
     *
     * @return array|null
     */
    public function row(int $flags = null): ?array
    {
        $row = $this->fetch($flags);
        if ($this->isAutoClose()) {
            $this->close();
        }

        return $row;
    }

    /**
     * Returns first column with $index
     *
     * @param int      $index
     * @param int|null $flags One or more of VV\Db::FETCH_*
     *
     * @return array
     */
    public function column(int $index = 0, int $flags = null): array
    {
        return $this->rows($flags | Db::FETCH_NUM, decorator: $index);
    }

    /**
     * Returns array of all fetched rows
     *
     * @param int|null                 $flags     One or more of VV\Db::FETCH_*
     * @param string|int|null          $keyColumn If passed then key of each row will be element of current row with
     *                                            same index
     * @param string|int|\Closure|null $decorator Applies for each row by passing current $row and $key as arguments.
     *                                            If it is string then each row will be element of row with this index
     *
     * @return array[] Array of rows
     */
    public function rows(int $flags = null, string|int $keyColumn = null, string|int|\Closure $decorator = null): array
    {
        if ($decorator === null) {
            $decorator = function () {
            };
        } elseif (is_scalar($decorator)) {
            $decorator = function (&$row) use ($decorator) {
                $row = $row[$decorator];
            };
        }

        $rows = [];
        while ($row = $this->fetch($flags)) {
            if ((string)$keyColumn !== '') {
                $key = $row[$keyColumn];
                if (count($row) > 1) {
                    unset($row[$keyColumn]);
                }
            } else {
                $key = false;
            }

            if ($decorator($row, $key) !== false) {
                if ($key !== false) {
                    $rows[$key] = $row;
                } else {
                    $rows[] = $row;
                }
            }
        }

        return $rows;
    }

    /**
     * @param string|int|null $keyColumn
     * @param string|int|null $valueColumn
     *
     * @return array
     */
    public function assoc(string|int $keyColumn = null, string|int $valueColumn = null): array
    {
        $valueColumn .= '';
        $keyColumn .= '';

        return $this->rows(
            decorator: function (&$row, &$key) use (&$valueColumn, &$keyColumn) {
                if ($keyColumn == '' || $valueColumn == '') {
                    $keys = array_keys($row);
                    if ($keyColumn == '') {
                        $keyColumn = $keys[0];
                    }
                    if ($valueColumn == '') {
                        $valueColumn = $keys[1] ?? $keys[0];
                    }
                }

                $key = $row[$keyColumn];
                $row = $row[$valueColumn];
            }
        );
    }

    public function insertedId(): int|string
    {
        $insertedId = $this->getDriverOrThrow()->getInsertedId();
        if ($insertedId === null) {
            throw new \UnexpectedValueException('insertedId is null');
        }

        return $insertedId;
    }

    public function affectedRows(): int
    {
        $affectedRows = $this->getDriverOrThrow()->getAffectedRows();
        if ($affectedRows === null) {
            throw new \UnexpectedValueException('affectedRows is null');
        }

        return $affectedRows;
    }

    /**
     * Closes query statement and frees resources
     *
     * @return $this
     */
    public function close(): self
    {
        if (!$this->isClosed()) {
            $this->driver->close();
            $this->driver = null;
            $this->prepared->close();
        }

        return $this;
    }

    /**
     * @return bool
     */
    public function isClosed(): bool
    {
        return empty($this->driver);
    }

    /**
     * @inheritDoc
     */
    public function getIterator(): \Traversable
    {
        if ($this->used) {
            $driver = $this->getDriverOrThrow();
            if ($driver instanceof Driver\SeekableResult) {
                $driver->seek(0);
            } else {
                throw new \RuntimeException('Repeated foreach not allowed: Result\Seekable not implemented');
            }
        }

        while ($row = $this->fetch()) {
            yield $row;
        }
    }

    /**
     * @return bool
     */
    public function isAutoClose(): bool
    {
        return $this->autoClose;
    }

    /**
     * @param bool $autoClose
     *
     * @return $this
     */
    public function setAutoClose(bool $autoClose): self
    {
        $this->autoClose = $autoClose;

        return $this;
    }

    /**
     * @return Driver\Result
     */
    protected function getDriverOrThrow(): Driver\Result
    {
        if (!$this->driver) {
            throw new \LogicException('Result is closed');
        }

        return $this->driver;
    }

    /**
     * @return array|null
     */
    protected function getResultColumnsMap(): ?array
    {
        if ($this->resultColumnsMap === false) {
            $this->resultColumnsMap = $this->prepared->getQueryInfo()->getResultColumnsMap() ?: null;
        }

        return $this->resultColumnsMap;
    }

    /**
     * @param       $row
     * @param array $resultColumnsMap
     */
    public static function acceptRowResultColumnsMap(&$row, array $resultColumnsMap): void
    {
        foreach ($resultColumnsMap as $key => $path) {
            $val = $row[$key];
            unset($row[$key]);

            $ref = &$row;
            foreach ($path as $p) {
                if ($ref && array_key_exists($p, $ref) && !is_array($ref[$p])) {
                    if ($ref[$p] === null) {
                        continue 2;
                    }
                    $ref[$p] = [];
                }
                $ref = &$ref[$p];
            }

            if (is_array($ref)) {
                if ($val === null) {
                    $ref = null;
                }
                continue;
            }

            $ref = $val;
        }
        unset($ref);
    }
}
