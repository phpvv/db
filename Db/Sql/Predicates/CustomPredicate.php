<?php declare(strict_types=1);

/*
 * This file is part of the VV package.
 *
 * (c) Volodymyr Sarnytskyi <v00v4n@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
namespace VV\Db\Sql\Predicates;

use VV\Db\Sql\Expressions\Expression;

/**
 * Class CustomPredicate
 *
 * @package VV\Db\Sql\Predicates
 */
class CustomPredicate extends PredicateBase
{
    private Expression $expression;
    private array $params;

    /**
     * CustomPredicate constructor.
     *
     * @param Expression $expression
     * @param array      $params
     * @param bool       $not
     */
    public function __construct(Expression $expression, array $params, bool $not = false)
    {
        parent::__construct($not);

        $this->expression = $expression;
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
     * @return array
     */
    public function getParams(): array
    {
        return $this->params;
    }
}
