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
 * Interface AliasableExpression
 *
 * @package VV\Db\Sql\Expressions
 */
interface AliasableExpression
{
    /**
     * @return string|null
     */
    public function getAlias(): ?string;

    /**
     * @param string|null $alias
     *
     * @return $this
     */
    public function as(?string $alias): static;
}
