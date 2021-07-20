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

namespace VV\Db\Sql\Expressions;

use VV\Db\Param;

/**
 * Class SqlParam
 *
 * @package VV\Db\Sql\Expressions
 */
class SqlParam implements Expression
{
    use AliasFieldTrait;

    private mixed $param;

    /**
     * SqlParam constructor.
     *
     * @param mixed $param
     */
    public function __construct(mixed $param)
    {
        $this->setParam($param);
    }

    /**
     * @return mixed
     */
    public function getParam(): mixed
    {
        return $this->param;
    }

    public function getExpressionId(): string
    {
        return spl_object_hash($this);
    }

    /**
     * @param Param|mixed $param
     *
     * @return $this
     */
    public function setParam(mixed $param): static
    {
        static::throwIfWrongType($param);
        $this->param = $param;

        return $this;
    }

    /**
     * @param mixed $param
     *
     * @return bool
     */
    public static function checkType(mixed $param): bool
    {
        return is_string($param)
               || is_int($param)
               || is_double($param)
               || is_null($param)
               // parameter object
               || (is_object($param)
                   && (
                       $param instanceof Param
                       || $param instanceof \DateTimeInterface
                   )
               );
    }

    /**
     * @param mixed $param
     */
    public static function throwIfWrongType(mixed $param)
    {
        if (!static::checkType($param)) {
            $type = is_object($param) ? get_class($param) : gettype($param);
            throw new \InvalidArgumentException(
                "Parameter can be string, integer, double or instance of VV\\Db\\P; $type given"
            );
        }
    }
}
