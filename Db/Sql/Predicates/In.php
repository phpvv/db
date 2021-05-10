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
 * Class In
 *
 * @package VV\Db\Sql\Predicate
 */
class In extends Base {

    /**
     * @var Expression
     */
    private $expr;

    /**
     * @var \VV\Db\Sql\Expressions\Expression[]|array
     */
    private $params;

    /**
     * IsNull constructor.
     *
     * @param \VV\Db\Sql\Expressions\Expression $expr
     * @param array                             $params
     * @param bool                              $not
     */
    public function __construct(Expression $expr, array $params, bool $not = false) {
        $this->expr = $expr;
        if (!$params) throw new \InvalidArgumentException('Params is empty');
        $this->params = $params;
        $this->not = $not;
    }

    /**
     * @return Expression
     */
    public function expr(): Expression {
        return $this->expr;
    }

    /**
     * @return \VV\Db\Sql\Expressions\Expression[]|array
     */
    public function params(): array {
        return $this->params;
    }
}
