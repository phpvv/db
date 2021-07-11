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
use VV\Db\Sql\Expressions\Expression;

/**
 * Class ReturnInto
 *
 * @package VV\Db\Sql\Clause
 * @method ReturnIntoClauseItem[] items():array
 */
class ReturnIntoClause extends ItemList
{

    /**
     * @param iterable|string|Expression $expression
     * @param mixed|Param|null           $param
     * @param int|null                   $type \VV\Db\P::T_...
     * @param string|null                $name
     * @param int|null                   $size Size of variable in bytes
     *
     * @return $this
     */
    public function add(
        iterable|string|Expression $expression,
        mixed &$param = null,
        int $type = null,
        string $name = null,
        int $size = null
    ): static {
        if (is_iterable($expression)) {
            foreach ($expression as $k => &$v) {
                $this->add($k, $v);
            }
            unset($v);

            return $this;
        }

        $P = !$param instanceof Param
            ? Param::getPointer($type ?: Param::T_CHR, $param, $name, $size)
            : $param;

        $item = $this->creteItem($expression, $P);
        $itemName = $item->expression()->expressionId();
        $this->items[$itemName] = $item;

        return $this;
    }

    /**
     * @return array
     */
    public function split(): array
    {
        $exprs = $params = [];
        foreach ($this->items() as $item) {
            $exprs[] = $item->expression();
            $params[] = $item->param();
        }

        return [$exprs, $params];
    }

    /**
     * @param string|Expression $field
     * @param Param             $param
     *
     * @return ReturnIntoClauseItem
     */
    protected function creteItem(string|Expression $field, Param $param): ReturnIntoClauseItem
    {
        return new ReturnIntoClauseItem($field, $param);
    }
}
