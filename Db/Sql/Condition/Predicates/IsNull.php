<?php declare(strict_types=1);

/*
 * This file is part of the VV package.
 *
 * (c) Volodymyr Sarnytskyi <v00v4n@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
namespace VV\Db\Sql\Condition\Predicates;

use VV\Db\Sql\Expression;

/**
 * Class IsNull
 *
 * @package VV\Db\Sql\Predicate
 */
class IsNull extends Base {

    /**
     * @var Expression
     */
    private $expr;

    /**
     * IsNull constructor.
     *
     * @param Expression $expr
     * @param bool       $not
     */
    public function __construct(Expression $expr, bool $not = false) {
        $this->expr = $expr;
        $this->not = $not;
    }

    /**
     * @return Expression
     */
    public function expr(): Expression {
        return $this->expr;
    }
}
