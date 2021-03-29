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
 * Class Field
 *
 * @package VV\Db\Model
 */
class Field {

    /** Short strings: VARCHAR, CHAR */
    const T_CHR = 1;

    /** Numeric fields like INT, FLOAT, NUMBER, DECIMAL... */
    const T_NUM = 2;

    /** Large text object like mysql TEXT or oracle CLOB */
    const T_TEXT = 3;

    /** Large binary object */
    const T_BLOB = 4;

    /** Raw short binary data like mysql BINARY, VARBINARY or orcale RAW */
    const T_BIN = 5;

    /** Datetime */
    const T_DATETIME = 6;

    /** Date */
    const T_DATE = 7;

    /** Time */
    const T_TIME = 8;

    const T_BOOL = 9;

    private string $name;

    private int $type;

    private ?int $length;

    private ?int $precision;

    private ?int $scale;

    private ?string $default;

    private bool $notnull;

    private bool $unsigned;

    public function __construct(string $name, array $data) {
        $this->name = $name;

        static $props = ['type', 'length', 'precision', 'scale', 'default', 'notnull', 'unsigned'];
        foreach ($props as $k => $v) $this->$v = $data[$k];
    }

    /**
     * @return string
     */
    public function name(): string {
        return $this->name;
    }

    /**
     * @return int
     */
    public function type(): int {
        return $this->type;
    }

    /**
     * @return int
     */
    public function length(): int {
        return $this->length;
    }

    /**
     * @return int|null
     */
    public function precision(): ?int {
        return $this->precision;
    }

    /**
     * @return int|null
     */
    public function scale(): ?int {
        return $this->scale;
    }

    /**
     * @return string|null
     */
    public function default(): ?string {
        return $this->default;
    }

    /**
     * @return bool
     */
    public function isNotnull(): bool {
        return $this->notnull;
    }

    /**
     * @return bool
     */
    public function isUnsigned(): bool {
        return $this->unsigned;
    }

    /**
     * Process data for saving
     *
     * @param mixed $value
     *
     * @return string|int|float|null
     */
    public function prepeareValueForCondition($value) {
        if (is_scalar($value)) {
            if ($this->type == self::T_NUM && !is_numeric($value)) $value = 0;
            if ((string)$value === '') $value = null;
        }

        return $value;
    }

    /**
     * Process data for saving
     *
     * @param mixed $value
     *
     * @return string|int|float|null
     */
    public function prepareValueToSave($value) {
        if (is_scalar($value)) {
            if ((string)$value === '') {
                if ($this->notnull && $this->type == self::T_NUM) $value = 0;
                else $value = null;
            }
        }

        return $value;
    }
}
