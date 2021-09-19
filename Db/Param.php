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

/**
 * Class Param
 *
 * @package VV\Db
 */
final class Param
{
    public const
        T_INT = 0b0001,
        T_FLOAT = 0b0011,
        T_BOOL = 0b0100,
        // T_NULL = 0b1000,
        T_STR = 0b0001 << 4,
        /** @deprecated */
        T_CHR = self::T_STR,
        T_BIN = 0b0011 << 4,
        T_TEXT = 0b0101 << 4,
        T_BLOB = 0b0111 << 4;

    public const
        T_MASK_NUM = 1,
        T_MASK_STR = 1 << 4,
        T_MASK_BIN = 1 << 5,
        T_MASK_LOB = 1 << 6;

    private const DFLT_LOB_WRITE_BLOCK_SIZE = 512 * 1024;

    private mixed $value;
    private int $type;
    private int $size = 0;
    private ?string $name = null;
    private int $lobWriteBlockSize = self::DFLT_LOB_WRITE_BLOCK_SIZE;
    private bool $forUpload = false;
    private bool $forInsertedId = false;
    private bool $bound = false;

    /**
     * Param constructor.
     *
     * @param Model\Field|int $type
     * @param mixed|null      $value
     * @param string|null     $name
     * @param int|null        $size
     */
    public function __construct(Model\Field|int $type, mixed $value = null, string $name = null, int $size = null)
    {
        if ($type instanceof Model\Field) {
            $type = self::getTypeByField($type);
        }
        $this->type = (int)$type;

        if ($value !== null) {
            $this->setValue($value);
        }
        if ($name !== null) {
            $this->setName($name);
        }
        if ($size !== null) {
            $this->setSize($size);
        }
    }

    /**
     * @return mixed
     */
    public function &getValue(): mixed
    {
        return $this->value;
    }

    /**
     * @param mixed $value
     *
     * @return $this
     */
    public function setValue(mixed $value): self
    {
        $this->value = $this->processValue($value);

        return $this;
    }

    /**
     * @param mixed $value
     *
     * @return $this
     */
    public function setValueRef(mixed &$value): self
    {
        $value = $this->processValue($value);
        $this->value = &$value;

        return $this;
    }

    /**
     * @return int
     */
    public function getType(): int
    {
        return $this->type;
    }

    /**
     * @return bool
     */
    public function isNumber(): bool
    {
        return (bool)($this->getType() & self::T_MASK_NUM);
    }

    /**
     * @return bool
     */
    public function isChar(): bool
    {
        return (bool)($this->getType() & self::T_MASK_STR);
    }

    /**
     * @return bool
     */
    public function isBin(): bool
    {
        return (bool)($this->getType() & self::T_MASK_BIN);
    }

    /**
     * @return bool
     */
    public function isLob(): bool
    {
        return (bool)($this->getType() & self::T_MASK_LOB);
    }

    /**
     * @return string|null
     */
    public function getName(): ?string
    {
        return $this->name;
    }

    /**
     * @param string|null $name
     *
     * @return $this
     */
    public function setName(?string $name): self
    {
        $this->name = $name;

        return $this;
    }

    /**
     * @return Param
     */
    public function setNextName(): self
    {
        static $nameCounter = 1;

        return $this->setName('pp' . ($nameCounter++));
    }

    /**
     * @return int
     */
    public function getSize(): int
    {
        return $this->size;
    }

    /**
     * @param int $size
     *
     * @return $this
     */
    public function setSize(int $size): self
    {
        if ($size) {
            if ($size < 0) {
                throw new \InvalidArgumentException('$size can\'t be less than 0');
            }

            if (!$this->isSizable()) {
                throw new \LogicException('Size not allowed for this param type');
            }

            if (($value = $this->getValue()) !== null) {
                $len = strlen((string)$value);
                if ($len > $size) {
                    throw new \LogicException("Can't set Size less than Value length ($size < $len)");
                }
            }

            $this->size = $size;
        }

        return $this;
    }

    /**
     * @return bool
     */
    public function isSizable(): bool
    {
        $type = $this->getType();

        return $type == self::T_STR || $type == self::T_BIN;
    }

    /**
     * @return bool
     */
    public function isForInsertedId(): bool
    {
        return $this->forInsertedId;
    }

    /**
     * @param bool $forInsertedId
     *
     * @return $this
     */
    public function setForInsertedId(bool $forInsertedId = true): self
    {
        $this->forInsertedId = $forInsertedId;

        return $this;
    }

    /**
     * @return bool
     */
    public function isForUpload(): bool
    {
        return $this->forUpload;
    }

    /**
     * @param bool $forUpload
     *
     * @return $this
     */
    public function setForUpload(bool $forUpload): self
    {
        $this->forUpload = $forUpload;

        return $this;
    }

    /**
     * @return int
     */
    public function getLobWriteBlockSize(): int
    {
        return $this->lobWriteBlockSize;
    }

    /**
     * @param int $lobWriteBlockSize
     *
     * @return $this
     */
    public function setLobWriteBlockSize(int $lobWriteBlockSize): self
    {
        $this->lobWriteBlockSize = $lobWriteBlockSize > 0 ? $lobWriteBlockSize : self::DFLT_LOB_WRITE_BLOCK_SIZE;

        return $this;
    }

    /**
     * @return bool
     */
    public function isBound(): bool
    {
        return $this->bound;
    }

    /**
     * @param bool $bound
     *
     * @return $this
     */
    public function setBound(bool $bound = true): self
    {
        $this->bound = $bound;

        return $this;
    }

    /**
     * @param mixed $value
     *
     * @return mixed
     */
    private function processValue(mixed $value): mixed
    {
        if ($value !== null) {
            switch ($this->type) {
                case self::T_TEXT:
                case self::T_BLOB:
                    if (\VV\Db\Param::isFileValue($value)) {
                        return \VV\readFileIterator($value, $this->getLobWriteBlockSize());
                    }
                    if (!is_iterable($value)) {
                        return \VV\readStringIterator((string)$value, $this->getLobWriteBlockSize());
                    }

                    break;
                case self::T_INT:
                    if (is_scalar($value)) {
                        return (int)$value;
                    }
                    break;
                case self::T_BIN:
                case self::T_STR:
                    if (is_scalar($value)) {
                        $value = (string)$value;

                        if (($size = $this->getSize()) || $this->isBound()) {
                            $len = strlen($value);
                            if ($len > $size) {
                                throw new \LogicException("Can't set Value longer than Size ($len > $size)");
                            }
                        }

                        return $value;
                    }

                    break;
            }
        }

        return $value;
    }

    /**
     * @param int|Model\Field $type
     * @param mixed           $value
     * @param string|null     $name
     * @param int|null        $size
     *
     * @return self
     */
    public static function getPointer(
        Model\Field|int $type,
        mixed &$value,
        string $name = null,
        int $size = null
    ): self {
        return (new self($type, null, $name, $size))->setValueRef($value);
    }

    public static function int(int $value = null, string $name = null, int $size = null): self
    {
        return new self(self::T_INT, $value, $name, $size);
    }

    public static function float(float $value = null, string $name = null, int $size = null): self
    {
        return new self(self::T_FLOAT, $value, $name, $size);
    }

    public static function bool(float $value = null, string $name = null, int $size = null): self
    {
        return new self(self::T_BOOL, $value, $name, $size);
    }

    public static function str(string $value = null, string $name = null, int $size = null): self
    {
        return new self(self::T_STR, $value, $name, $size);
    }

    /** @deprecated */
    public static function chr(string $value = null, string $name = null, int $size = null): self
    {
        return self::str(...func_get_args());
    }

    public static function bin(string $value = null, string $name = null, int $size = null): self
    {
        return new self(self::T_BIN, $value, $name, $size);
    }

    public static function text(iterable|string $value = null, string $name = null): self
    {
        return new self(self::T_TEXT, $value, $name);
    }

    public static function blob(iterable|string $value = null, string $name = null): self
    {
        return new self(self::T_BLOB, $value, $name);
    }

    /**
     * @param Model\Field $field
     *
     * @return int
     */
    public static function getTypeByField(Model\Field $field): int
    {
        return match ($field->getType()) {
            Model\Field::T_TEXT => self::T_TEXT,
            Model\Field::T_BLOB => self::T_BLOB,
            Model\Field::T_BIN => self::T_BIN,
            default => self::T_STR,
        };
    }

    /**
     * @param mixed $value
     *
     * @return bool
     */
    public static function isFileValue(mixed $value): bool
    {
        return \VV\isStream($value) || $value instanceof \SplFileObject;
    }
}
