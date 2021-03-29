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

/**
 * Class QueryInfo
 *
 * @package VV\Db\Driver
 */
class QueryInfo {

    const T_SELECT = 'select',
        T_INSERT = 'insert',
        T_UPDATE = 'update',
        T_DELETE = 'delete',
        T_REPLACE = 'replace',
        T_MERGE = 'merge';

    /**
     * @var string
     */
    private $string;

    /**
     * @var string
     */
    private $type;

    /**
     * @var bool
     */
    private $isModificatory;

    /**
     * @var array
     */
    private $resultFieldsMap;

    public function __construct($queryString, array $resultFieldsMap = null) {
        $this->string = $queryString;

        if (!preg_match('/^(?:\s*\()?\s*([a-z]+)/i', $queryString, $m))
            throw new \VV\Db\Exceptions\SqlSyntaxError('Can\'t determine sql type');

        $this->type = strtolower($m[1]);
        $this->isModificatory = null;
        $this->resultFieldsMap = $resultFieldsMap;

        return $this;
    }

    /**
     * @return string
     */
    public function string() {
        return $this->string;
    }

    /**
     * @return string
     */
    public function type() {
        return $this->type;
    }

    /**
     * @return bool
     */
    public function isModificatory() {
        if (null === $c = &$this->isModificatory)
            $c = self::isModificatoryType($this->type);

        return $c;
    }

    /**
     * @return bool
     */
    public function isSelect() {
        return $this->type == self::T_SELECT;
    }

    /**
     * @return bool
     */
    public function isInsert() {
        return $this->type == self::T_INSERT;
    }

    /**
     * @return bool
     */
    public function isUpdate() {
        return $this->type == self::T_UPDATE;
    }

    /**
     * @return bool
     */
    public function isDelete() {
        return $this->type == self::T_DELETE;
    }

    /**
     * @return bool
     */
    public function isReplace() {
        return $this->type == self::T_REPLACE;
    }

    /**
     * @return bool
     */
    public function isMerge() {
        return $this->type == self::T_MERGE;
    }

    /**
     * @return array
     */
    public function resultFieldsMap(): ?array {
        return $this->resultFieldsMap;
    }

    public static function isModificatoryType($type) {
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
