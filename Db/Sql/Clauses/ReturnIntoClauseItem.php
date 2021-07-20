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

namespace VV\Db\Sql\Clauses;

use VV\Db\Param;
use VV\Db\Sql\Expressions\DbObject;
use VV\Db\Sql\Expressions\Expression;

/**
 * Class Item
 *
 * @package VV\Db\Sql\Clause\ReturnInto
 */
class ReturnIntoClauseItem
{

    private Expression $expression;
    private Param $param;

    /**
     * Item constructor.
     *
     * @param Expression|string $expression
     * @param Param             $param
     */
    public function __construct(string|Expression $expression, Param $param)
    {
        if (!$expression instanceof Expression) {
            /** @var DbObject $expression */
            $expression = DbObject::create($expression);
            if (!$expression) {
                throw new \InvalidArgumentException();
            }
        }

        $this->expression = $expression;
        $this->param = $param;
    }

    /**
     * @return Expression
     */
    public function getExpression(): Expression
    {
        return $this->expression;
    }

    /**
     * @return Param
     */
    public function getParam(): Param
    {
        return $this->param;
    }
}
