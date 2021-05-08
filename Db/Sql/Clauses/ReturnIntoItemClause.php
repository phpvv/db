<?php declare(strict_types=1);

/*
 * This file is part of the VV package.
 *
 * (c) Volodymyr Sarnytskyi <v00v4n@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
namespace VV\Db\Sql\Clauses;

use VV\Db\Sql;

/**
 * Class Item
 *
 * @package VV\Db\Sql\Clause\ReturnInto
 */
class ReturnIntoItemClause {

    /**
     * @var Sql\Expression
     */
    private $expr;

    /**
     * @var \VV\Db\Param
     */
    private $param;

    /**
     * Item constructor.
     *
     * @param Sql\Expression|string $expr
     * @param \VV\Db\Param          $param
     *
     */
    public function __construct($expr, \VV\Db\Param $param) {
        $this->setExpr($expr)->setParam($param);
    }

    /**
     * @return Sql\Expression
     */
    public function expr() {
        return $this->expr;
    }

    /**
     * @return \VV\Db\Param
     */
    public function param() {
        return $this->param;
    }

    /**
     * @param Sql\Expression|string $expr
     *
     * @return $this
     */
    protected function setExpr($expr) {
        if ($o = Sql\DbObject::create($expr)) {
            $expr = $o;
        } elseif (!$expr instanceof Sql\Expression) {
            throw new \InvalidArgumentException;
        }

        $this->expr = $expr;

        return $this;
    }

    /**
     * @param \VV\Db\Param $param
     *
     * @return $this
     */
    protected function setParam(\VV\Db\Param $param) {
        $this->param = $param;

        return $this;
    }
}
