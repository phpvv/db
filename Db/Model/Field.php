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
class Field
{
    /** Short strings: VARCHAR, CHAR */
    public const T_CHR = 1;
    /** Numeric fields like INT, FLOAT, NUMBER, DECIMAL... */
    public const T_NUM = 2;
    /** Large text object like mysql TEXT or oracle CLOB */
    public const T_TEXT = 3;
    /** Large binary object */
    public const T_BLOB = 4;
    /** Raw short binary data like mysql BINARY, VARBINARY or orcale RAW */
    public const T_BIN = 5;
    /** Date and Time */
    public const T_DATETIME = 6;
    /** Date */
    public const T_DATE = 7;
    /** Time */
    public const T_TIME = 8;
    /** Boolean */
    public const T_BOOL = 9;

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

    /**
     * @return string
     */
    public function getName(): string
    {
        return $this->name;
    }

    /**
     * @return int
     */
    public function getType(): int
    {
        return $this->type;
    }

    /**
     * @return int
     */
    public function getLength(): int
    {
        return $this->length;
    }

    /**
     * @return int|null
     */
    public function getIntSize(): ?int
    {
        return $this->intSize;
    }

    /**
     * @return int|null
     */
    public function getPrecision(): ?int
    {
        return $this->precision;
    }

    /**
     * @return int|null
     */
    public function getScale(): ?int
    {
        return $this->scale;
    }

    /**
     * @return string|null
     */
    public function getDefaultValue(): ?string
    {
        return $this->defaultValue;
    }

    /**
     * @return bool
     */
    public function isNotNull(): bool
    {
        return $this->notNull;
    }

    /**
     * @return bool
     */
    public function isUnsigned(): bool
    {
        return $this->unsigned;
    }

    /**
     * Process data for saving
     *
     * @param mixed $value
     *
     * @return mixed
     */
    public function prepeareValueForCondition(mixed $value): mixed
    {
        if (is_scalar($value)) {
            if ($this->type == self::T_NUM && !is_numeric($value)) {
                $value = 0;
            }
            if ((string)$value === '') {
                $value = null;
            }
        }

        return $value;
    }

    /**
     * Process data for saving
     *
     * @param mixed $value
     *
     * @return mixed
     */
    public function prepareValueToSave(mixed $value): mixed
    {
        if (is_scalar($value)) {
            if ((string)$value === '') {
                if ($this->notNull && $this->type == self::T_NUM) {
                    $value = 0;
                } else {
                    $value = null;
                }
            }
        }

        return $value;
    }
}
