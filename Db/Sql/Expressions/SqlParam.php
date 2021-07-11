<?php declare(strict_types=1);

/*
 * This file is part of the VV package.
 *
 * (c) Volodymyr Sarnytskyi <v00v4n@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
namespace VV\Db\Sql\Expressions;

/**
 * Class Param
 *
 * @package VV\Db\Sql
 */
class SqlParam implements Expression
{

    use AliasFieldTrait;

    private mixed $param;

    /**
     * Param constructor.
     *
     * @param \VV\Db\Param|mixed $param
     */
    public function __construct(mixed $param)
    {
        $this->setParam($param);
    }

    /**
     * @return mixed
     */
    public function param(): mixed
    {
        return $this->param;
    }

    public function expressionId(): string
    {
        return spl_object_hash($this);
    }

    /**
     * @param \VV\Db\Param|mixed $param
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
                       $param instanceof \VV\Db\Param
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
            throw new \InvalidArgumentException("Parameter can be string, integer, double or instance of VV\\Db\\P; $type given");
        }
    }
}
