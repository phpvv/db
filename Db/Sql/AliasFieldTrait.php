<?php declare(strict_types=1);

/*
 * This file is part of the VV package.
 *
 * (c) Volodymyr Sarnytskyi <v00v4n@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
namespace VV\Db\Sql;

/**
 * Trait AliasFieldTrait
 *
 * @package VV\Db\Sql
 */
trait AliasFieldTrait {

    private ?string $alias = null;

    /**
     * @return string|null
     */
    public function alias(): ?string {
        return $this->alias;
    }

    /**
     * @param string|null $alias
     *
     * @return $this
     */
    public function as(?string $alias): static {
        $this->alias = $alias;

        return $this;
    }
}
