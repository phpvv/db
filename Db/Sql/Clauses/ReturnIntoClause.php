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

use VV\Db\Param;

/**
 * Class ReturnInto
 *
 * @package VV\Db\Sql\Clause
 * @method \VV\Db\Sql\Clauses\ReturnIntoItemClause[] items():array
 */
class ReturnIntoClause extends ItemList {

    /**
     * @param string|array|\Traversable|\VV\Db\Sql\Expression $expr
     * @param mixed|Param                                     $param
     * @param int                                             $type \VV\Db\P::T_...
     * @param string                                          $name
     * @param int                                             $size Size of variable in bytes
     *
     * @return $this
     */
    public function add($expr, &$param = null, $type = null, $name = null, $size = null) {
        if (is_iterable($expr)) {
            foreach ($expr as $k => &$v) $this->add($k, $v);
            unset($v);

            return $this;
        }

        $P = !$param instanceof Param
            ? Param::ptr($type ?: Param::T_CHR, $param, $name, $size)
            : $param;

        $item = $this->creteItem($expr, $P);
        $itemName = $item->expr()->exprId();
        $this->items[$itemName] = $item;

        return $this;
    }

    /**
     * @return array
     */
    public function split() {
        $exprs = $params = [];
        /** @var \VV\Db\Sql\Clauses\ReturnIntoItemClause $item */
        foreach ($this->items() as $item) {
            $exprs[] = $item->expr();
            $params[] = $item->param();
        }

        return [$exprs, $params];
    }

    /**
     * @param string|\VV\Db\Sql\Expression $field
     * @param Param                        $param
     *
     * @return \VV\Db\Sql\Clauses\ReturnIntoItemClause
     */
    protected function creteItem($field, Param $param) {
        return new \VV\Db\Sql\Clauses\ReturnIntoItemClause($field, $param);
    }
}
