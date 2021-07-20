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

namespace VV\Db\Sql\Predicates;

use VV\Db\Sql\Expressions\Expression;

/**
 * Class InPredicate
 *
 * @package VV\Db\Sql\Predicates
 */
class InPredicate extends PredicateBase
{
    private Expression $expression;
    /** @var Expression[] */
    private array $params;

    /**
     * InPredicate constructor.
     *
     * @param Expression $expression
     * @param array      $params
     * @param bool       $not
     */
    public function __construct(Expression $expression, array $params, bool $not = false)
    {
        parent::__construct($not);

        $this->expression = $expression;
        if (!$params) {
            throw new \InvalidArgumentException('Params is empty');
        }
        $this->params = $params;
    }

    /**
     * @return Expression
     */
    public function getExpression(): Expression
    {
        return $this->expression;
    }

    /**
     * @return Expression[]
     */
    public function getParams(): array
    {
        return $this->params;
    }
}
