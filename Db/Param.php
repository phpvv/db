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

use JetBrains\PhpStorm\Pure;

/**
 * Class Param
 *
 * @package VV\Db
 */
final class Param
{
    public const T_CHR = 1,
        T_INT = 2,
        T_TEXT = 3,
        T_BLOB = 4,
        T_BIN = 5;

    private const DFLT_LOB_WRITE_BLOCK_SIZE = 512 * 1024;

    private mixed $value;
    private int $type;
    private int $size = 0;
    private ?string $name = null;
    private int $lobWriteBlockSize = self::DFLT_LOB_WRITE_BLOCK_SIZE;
    private bool $forUpload = false;
    private bool $forInsertedId = false;
    private bool $binded = false;

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
    public function isLob(): bool
    {
        static $lobTypes = [self::T_TEXT, self::T_BLOB];

        return in_array($this->getType(), $lobTypes);
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
                $strlen = strlen((string)$value);
                if ($strlen > $size) {
                    throw new \LogicException("Can't set Size less than Value length ($size < $strlen)");
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

        return $type == self::T_CHR || $type == self::T_BIN;
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
    public function isBinded(): bool
    {
        return $this->binded;
    }

    /**
     * @param bool $binded
     *
     * @return $this
     */
    public function setBinded(bool $binded = true): self
    {
        $this->binded = $binded;

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
                case self::T_CHR:
                    if (is_scalar($value)) {
                        $value = (string)$value;

                        if (($size = $this->getSize()) || $this->isBinded()) {
                            $strlen = strlen($value);
                            if ($strlen > $size) {
                                throw new \LogicException("Can't set Value longer than Size ($strlen > $size)");
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

    /**
     * @param string|null $value
     * @param string|null $name
     * @param int|null    $size
     *
     * @return self
     */
    public static function chr(string $value = null, string $name = null, int $size = null): self
    {
        return new self(self::T_CHR, $value, $name, $size);
    }

    /**
     * @param int|null    $value
     * @param string|null $name
     * @param int|null    $size
     *
     * @return self
     */
    public static function int(int $value = null, string $name = null, int $size = null): self
    {
        return new self(self::T_INT, $value, $name, $size);
    }

    /**
     * @param iterable|string|null $value
     * @param string|null          $name
     *
     * @return self
     */
    public static function text(iterable|string $value = null, string $name = null): self
    {
        return new self(self::T_TEXT, $value, $name);
    }

    /**
     * @param iterable|string|null $value
     * @param string|null          $name
     *
     * @return self
     */
    public static function blob(iterable|string $value = null, string $name = null): self
    {
        return new self(self::T_BLOB, $value, $name);
    }

    /**
     * @param string|null $value
     * @param string|null $name
     * @param int|null    $size
     *
     * @return self
     */
    public static function bin(string $value = null, string $name = null, int $size = null): self
    {
        return new self(self::T_BIN, $value, $name, $size);
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
            default => self::T_CHR,
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
