<?php declare(strict_types=1);

/*
 * This file is part of the VV package.
 *
 * (c) Volodymyr Sarnytskyi <v00v4n@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
namespace VV\Db;

/**
 * Class Result
 *
 * @property-read int    $affectedRows Returns number of rows affected during statement execution
 *
 * @property-read string $column
 * @property-read array  $row
 * @property-read array  $rows
 * @property-read array  $assoc
 */
final class Result implements \IteratorAggregate {

    private ?Driver\Result $driver;

    private Statement $prepared;

    /** @var \Generator[] */
    private array $fetchGeneratorList = [];

    private ?\Closure $decorator = null;

    private int $flags = \VV\Db::FETCH_ASSOC;

    private bool $autoClose = false;

    private bool $used = false;

    private array|bool|null $resultFieldsMap = false;

    /**
     * Result constructor.
     *
     * @param Driver\Result $driver
     * @param Statement     $prepared
     */
    public function __construct(Driver\Result $driver, Statement $prepared) {
        $this->driver = $driver;
        $this->prepared = $prepared;
    }

    public function __get($var) {
        return match ($var) {
            'column' => $this->column(),
            'row' => $this->row(),
            'rows' => $this->rows(),
            'assoc' => $this->assoc(),
            'insertedId' => $this->insertedId(),
            'affectedRows' => $this->affectedRows(),
            default => throw new \LogicException("Undefined property $var"),
        };
    }

    /**
     * @return int
     */
    public function flags(): int {
        return $this->flags;
    }

    /**
     * Sets default flags
     *
     * @param int|null $flags
     *
     * @return $this
     */
    public function setFlags(?int $flags): self {
        $this->flags = $flags ?? \VV\Db::FETCH_ASSOC;

        return $this;
    }

    /**
     * @return \Closure|null
     */
    public function decorator(): ?\Closure {
        return $this->decorator;
    }

    /**
     * @param string|\Closure|null $decorator
     *
     * @return $this
     */
    public function setDecorator(string|\Closure|null $decorator): self {
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
    public function fetch(int $flags = null): ?array {
        $driver = $this->driverOrThrow();

        $this->used = true;
        if ($flags === null) $flags = $this->flags;

        // get fetch generator from driver
        $generator = &$this->fetchGeneratorList[$flags];
        if (!$generator) $generator = $driver->fetchIterator($flags);

        $row = $generator->current();
        if ($row) {
            $generator->next();

            // make sub arrays
            if (is_array($row) && $resultFieldsMap = $this->resultFieldsMap()) {
                self::acceptRowResultFieldMap($row, $resultFieldsMap);
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
     * Returns first cell of first row
     *
     * @param int      $index
     * @param int|null $flags One or more of VV\Db::FETCH_*
     *
     * @return mixed
     */
    public function column(int $index = 0, int $flags = null): mixed {
        $row = $this->row($flags | \VV\Db::FETCH_NUM);
        if (!$row) return null;

        return $row[$index];
    }

    /**
     * Returns first row
     *
     * @param int|null $flags One or more of VV\Db::FETCH_*
     *
     * @return array|null
     */
    public function row(int $flags = null): ?array {
        $row = $this->fetch($flags);
        if ($this->isAutoClose()) $this->close();

        return $row;
    }

    /**
     * Returns array of all fetched rows
     *
     * @param int|null             $flags     One or more of VV\Db::FETCH_*
     * @param string|int|null      $keyField  If passed then key of each row will be element of current row with same
     *                                        index
     * @param string|\Closure|null $decorator Applies for each row by passing current $row and $key as arguments.
     *                                        If it is string then each row will be element of row with this index
     *
     * @return array[] Array of rows
     */
    public function rows(int $flags = null, $keyField = null, $decorator = null): array {
        if (!$decorator) {
            $decorator = function () { };
        } elseif (is_scalar($decorator)) {
            $decorator = function (&$row) use ($decorator) {
                $row = $row[$decorator];
            };
        }

        $rows = [];
        while ($row = $this->fetch($flags)) {
            if ((string)$keyField !== '') {
                $key = $row[$keyField];
                if (count($row) > 1) unset($row[$keyField]);
            } else $key = false;

            if ($decorator($row, $key) !== false) {
                if ($key !== false) {
                    $rows[$key] = $row;
                } else $rows[] = $row;
            }
        }

        return $rows;
    }

    /**
     * @param string|null $keyField
     * @param string|null $valueField
     *
     * @return array
     */
    public function assoc($keyField = null, $valueField = null): array {
        $valueField .= '';
        $keyField .= '';

        return $this->rows(null, null, function (&$row, &$k) use (&$valueField, &$keyField) {
            if ($keyField === '' || $valueField === '') {
                $keys = array_keys($row);
                if ($keyField === '') $keyField = $keys[0];
                if ($valueField === '') $valueField = empty($keys[1]) ? $keys[0] : $keys[1];
            }

            $k = $row[$keyField];
            $row = $row[$valueField];
        });
    }

    public function insertedId() {
        $insertedId = $this->driverOrThrow()->insertedId();
        if ($insertedId === null) throw new \UnexpectedValueException('insertedId is null');

        return $insertedId;
    }

    public function affectedRows(): int {
        $affectedRows = $this->driverOrThrow()->affectedRows();
        if ($affectedRows === null) throw new \UnexpectedValueException('affectedRows is null');

        return $affectedRows;
    }

    /**
     * Closes query statement and frees resources
     *
     * @return $this
     */
    public function close(): self {
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
    public function isClosed(): bool {
        return empty($this->driver);
    }

    /**
     * @inheritDoc
     */
    public function getIterator(): \Traversable {
        if ($this->used) {
            $driver = $this->driverOrThrow();
            if ($driver instanceof Driver\SeekableResult) {
                $driver->seek(0);
            } else {
                throw new \RuntimeException('Repeated foreach not allowed: Result\Seekable not implemented');
            }
        }

        while ($row = $this->fetch()) yield $row;
    }

    /**
     * @return bool
     */
    public function isAutoClose(): bool {
        return $this->autoClose;
    }

    /**
     * @param boolean $autoClose
     *
     * @return $this
     */
    public function setAutoClose(bool $autoClose): self {
        $this->autoClose = $autoClose;

        return $this;
    }

    /**
     * @return Driver\Result
     */
    protected function driverOrThrow(): Driver\Result {
        if (!$this->driver) throw new \LogicException('Result is closed');

        return $this->driver;
    }

    /**
     * @return array|null
     */
    protected function resultFieldsMap(): ?array {
        if ($this->resultFieldsMap === false) {
            $this->resultFieldsMap = $this->prepared->queryInfo()->resultFieldsMap() ?: null;
        }

        return $this->resultFieldsMap;
    }

    /**
     * @param $row
     * @param $resultFieldsMap
     */
    public static function acceptRowResultFieldMap(&$row, $resultFieldsMap): void {
        foreach ($resultFieldsMap as $key => $path) {
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
