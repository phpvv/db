<?php declare(strict_types=1);

/*
 * This file is part of the VV package.
 *
 * (c) Volodymyr Sarnytskyi <v00v4n@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
namespace VV\Db\Sql;

/**
 * Class Param
 *
 * @package VV\Db\Sql
 */
class Param implements Expression {

    use AliasFieldTrait;

    /**
     * @var \VV\Db\Param|mixed
     */
    private $param;

    /**
     * Param constructor.
     *
     * @param \VV\Db\Param|mixed $param
     */
    public function __construct($param) {
        $this->setParam($param);
    }

    /**
     * @return \VV\Db\Param|mixed
     */
    public function param() {
        return $this->param;
    }

    public function exprId(): string {
        return spl_object_hash($this);
    }

    /**
     * @param \VV\Db\Param|mixed $param
     *
     * @return $this
     */
    public function setParam($param) {
        static::throwIfWrongType($param);
        $this->param = $param;

        return $this;
    }

    /**
     * @param mixed $param
     *
     * @return bool
     */
    public static function checkType($param) {
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
    public static function throwIfWrongType($param) {
        if (!static::checkType($param)) {
            $type = is_object($param) ? get_class($param) : gettype($param);
            throw new \InvalidArgumentException("Parameter can be string, integer, double or instance of VV\\Db\\P; $type given");
        }
    }
}
