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
     * IsNull constructor.
     *
     * @param Expression   $expression
     * @param Expression[] $params
     * @param bool         $not
     */
    public function __construct(Expression $expression, array $params, bool $not = false)
    {
        $this->expression = $expression;
        if (!$params) {
            throw new \InvalidArgumentException('Params is empty');
        }
        $this->params = $params;
        $this->not = $not;
    }

    /**
     * @return Expression
     */
    public function expression(): Expression
    {
        return $this->expression;
    }

    /**
     * @return Expression[]
     */
    public function params(): array
    {
        return $this->params;
    }
}
