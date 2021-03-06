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

namespace VV\Db\Sql\Expressions;

/**
 * Interface Expression
 *
 * @package VV\Db\Sql\Expressions
 */
interface Expression extends AliasableExpression
{
    /**
     * Returns unique ID of Expression
     *
     * @return string
     */
    public function getExpressionId(): string;
}
