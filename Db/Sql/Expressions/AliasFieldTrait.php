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
 * Trait AliasFieldTrait
 *
 * @package VV\Db\Sql\Expressions
 */
trait AliasFieldTrait
{
    private ?string $alias = null;

    /**
     * @return string|null
     */
    public function getAlias(): ?string
    {
        return $this->alias;
    }

    /**
     * @param string|null $alias
     *
     * @return $this
     */
    public function as(?string $alias): static
    {
        $this->alias = $alias;

        return $this;
    }
}
