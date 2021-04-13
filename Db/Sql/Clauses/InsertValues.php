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
 * Class InsertValues
 *
 * @package VV\Db\Sql\Clause
 */
class InsertValues extends ItemList {


    /**
     * Add field(s)
     *
     * @param string|\VV\Db\Param|Sql\Expression ...$values
     *
     * @return $this
     */
    public function add(...$values): static {
        if (count($values) == 1 && $values[0] instanceof Sql\SelectQuery) {
            $values = $values[0];
        } else {
            if (count($values) == 1 && is_array($values[0])) {
                $values = $values[0];
            }

            $allowedObjTypes = [\VV\Db\Param::class, Sql\Expression::class, \DateTimeInterface::class];
            foreach ($values as $i => &$v) {
                if (is_object($v)) {
                    if (!\VV\instOf($v, ...$allowedObjTypes)) {
                        throw new \InvalidArgumentException("Wrong type of values #$i");
                    }
                } elseif (!is_scalar($v) && $v !== null) {
                    throw new \InvalidArgumentException("Value #$i is not scalar type");
                }
            }
            unset($v);
        }

        $this->items[] = $values;

        return $this;
    }
}
