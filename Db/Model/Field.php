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
 * Class Field
 *
 * @package VV\Db\Model
 */
class Field
{
    /** Integer fields like INT, BIGINT, SMALLINT... */
    public const T_INT = 0b0001;
    /** Numeric fields with precision like FLOAT, NUMBER, DECIMAL... */
    public const T_NUM = 0b0011;
    /** Boolean */
    public const T_BOOL = 0b0100;

    /** Short strings: VARCHAR, CHAR */
    public const T_CHR = self::T_MASK_STR;
    /** Raw short binary data like mysql BINARY, VARBINARY or oracle RAW */
    public const T_BIN = self::T_MASK_STR | self::T_MASK_BIN;
    /** Large text object like mysql TEXT or oracle CLOB */
    public const T_TEXT = self::T_MASK_STR | self::T_MASK_LOB;
    /** Large binary object */
    public const T_BLOB = self::T_MASK_STR | self::T_MASK_LOB | self::T_MASK_BIN;

    /** Date */
    public const T_DATE = self::T_MASK_DATE;
    /** Time */
    public const T_TIME = self::T_MASK_TIME;
    /** Time with Time Zone */
    public const T_TIME_TZ = self::T_MASK_TIME | self::T_MASK_TIME_ZONE;
    /** Date and Time */
    public const T_DATETIME = self::T_MASK_DATE | self::T_MASK_TIME;
    /** Date and Time with Time Zone*/
    public const T_DATETIME_TZ = self::T_DATETIME | self::T_MASK_TIME_ZONE;

    public const
        T_MASK_NUM = 1,
        // string masks
        T_MASK_STR = 0b0001 << 4,
        T_MASK_BIN = 0b0010 << 4,
        T_MASK_LOB = 0b0100 << 4,
        // date & time masks
        T_MASK_DATE = 0b0001 << 8,
        T_MASK_TIME = 0b0100 << 8,
        T_MASK_DATETIME = self::T_MASK_DATE | self::T_MASK_TIME,
        T_MASK_TIME_ZONE = 0b1000 << 8;

    private string $name;
    private int $type;
    private ?int $length;
    private ?int $intSize;
    private ?int $precision;
    private ?int $scale;
    private ?string $defaultValue;
    private bool $notNull;
    private bool $unsigned;

    public function __construct(string $name, array $data)
    {
        $this->name = $name;
        [
            $this->type,
            $this->length,
            $this->intSize,
            $this->precision,
            $this->scale,
            $this->defaultValue,
            $this->notNull,
            $this->unsigned,
        ] = $data;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getType(): int
    {
        return $this->type;
    }

    public function isNumeric(): bool
    {
        return ($this->getType() & self::T_MASK_NUM) == self::T_MASK_NUM;
    }

    public function isStringable(): bool
    {
        return ($this->getType() & self::T_MASK_STR) == self::T_MASK_STR;
    }

    public function isBinary(): bool
    {
        return ($this->getType() & self::T_MASK_BIN) == self::T_MASK_BIN;
    }

    public function hasDate(): bool
    {
        return ($this->getType() & self::T_MASK_DATE) == self::T_MASK_DATE;
    }

    public function hasTime(): bool
    {
        return ($this->getType() & self::T_MASK_TIME) == self::T_MASK_TIME;
    }

    public function hasDateTime(): bool
    {
        return ($this->getType() & self::T_MASK_DATETIME) == self::T_MASK_DATETIME;
    }

    public function hasTimeZone(): bool
    {
        return ($this->getType() & self::T_MASK_TIME_ZONE) == self::T_MASK_TIME_ZONE;
    }

    public function getLength(): int
    {
        return $this->length;
    }

    public function getIntSize(): ?int
    {
        return $this->intSize;
    }

    public function getPrecision(): ?int
    {
        return $this->precision;
    }

    public function getScale(): ?int
    {
        return $this->scale;
    }

    public function getDefaultValue(): ?string
    {
        return $this->defaultValue;
    }

    public function isNotNull(): bool
    {
        return $this->notNull;
    }

    public function isUnsigned(): bool
    {
        return $this->unsigned;
    }
}
