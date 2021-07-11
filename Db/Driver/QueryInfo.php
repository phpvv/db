<?php declare(strict_types=1);

/*
 * This file is part of the VV package.
 *
 * (c) Volodymyr Sarnytskyi <v00v4n@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
namespace VV\Db\Driver;

use VV\Db\Exceptions\SqlSyntaxError;

/**
 * Class QueryInfo
 *
 * @package VV\Db\Driver
 */
class QueryInfo
{

    public const T_SELECT = 'select',
        T_INSERT = 'insert',
        T_UPDATE = 'update',
        T_DELETE = 'delete',
        T_REPLACE = 'replace',
        T_MERGE = 'merge';

    private string $string;
    private string $type;
    private ?bool $isModificatory;
    private ?array $resultFieldsMap;

    public function __construct($queryString, array $resultFieldsMap = null)
    {
        $this->string = $queryString;

        if (!preg_match('/^(?:\s*\()?\s*([a-z]+)/i', $queryString, $m)) {
            throw new SqlSyntaxError('Can\'t determine sql type');
        }

        $this->type = strtolower($m[1]);
        $this->isModificatory = null;
        $this->resultFieldsMap = $resultFieldsMap;

        return $this;
    }

    /**
     * @return string
     */
    public function getString(): string
    {
        return $this->string;
    }

    /**
     * @return string
     */
    public function getType(): string
    {
        return $this->type;
    }

    /**
     * @return bool
     */
    public function isModificatory(): bool
    {
        if (null === $c = &$this->isModificatory) {
            $c = self::isModificatoryType($this->type);
        }

        return $c;
    }

    /**
     * @return bool
     */
    public function isSelect(): bool
    {
        return $this->type == self::T_SELECT;
    }

    /**
     * @return bool
     */
    public function isInsert(): bool
    {
        return $this->type == self::T_INSERT;
    }

    /**
     * @return bool
     */
    public function isUpdate(): bool
    {
        return $this->type == self::T_UPDATE;
    }

    /**
     * @return bool
     */
    public function isDelete(): bool
    {
        return $this->type == self::T_DELETE;
    }

    /**
     * @return bool
     */
    public function isReplace(): bool
    {
        return $this->type == self::T_REPLACE;
    }

    /**
     * @return bool
     */
    public function isMerge(): bool
    {
        return $this->type == self::T_MERGE;
    }

    /**
     * @return array|null
     */
    public function getResultFieldsMap(): ?array
    {
        return $this->resultFieldsMap;
    }

    public static function isModificatoryType($type): bool
    {
        switch ($type) {
            case self::T_INSERT:
            case self::T_UPDATE:
            case self::T_DELETE:
            case self::T_REPLACE:
            case self::T_MERGE:
                return true;
        }

        return false;
    }
}
