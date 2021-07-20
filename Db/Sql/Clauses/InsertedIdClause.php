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

/**
 * Class InsertedIdClause
 *
 * @package VV\Db\Sql\Clauses
 */
class InsertedIdClause implements Clause
{
    private ?Param $param = null;
    private bool $empty = true;
    private ?string $pk = null;

    /**
     * @param int|Param|null $type
     * @param int|null       $size
     * @param string|null    $pk
     *
     * @return $this
     */
    public function set(Param|int $type = null, int $size = null, string $pk = null): self
    {
        $this->empty = false;

        if ($type) {
            if ($type instanceof Param) {
                $param = $type;
            } else {
                $param = new Param($type, null, null, $size);
            }
            if ($size !== null) {
                $param->setSize($size);
            }

            $this->param = $param;
        }
        $this->pk = $pk;

        return $this;
    }

    /**
     * @return mixed
     */
    public function getValue(): mixed
    {
        if (!$this->param) {
            throw new \InvalidArgumentException('Param is empty');
        }

        return $this->param->getValue();
    }

    /**
     * @return Param|null
     */
    public function getParam(): ?Param
    {
        return $this->param;
    }

    /**
     * @return string|null
     */
    public function getPk(): ?string
    {
        return $this->pk;
    }

    /**
     * @inheritdoc
     */
    public function isEmpty(): bool
    {
        return $this->empty;
    }
}
