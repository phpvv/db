<?php declare(strict_types=1);

/*
 * This file is part of the VV package.
 *
 * (c) Volodymyr Sarnytskyi <v00v4n@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
namespace VV\Db\Sql\Clauses;

/**
 * Class InsertedId
 *
 * @package VV\Db\Sql\Clause
 */
class InsertedId implements Clause {

    private ?\VV\Db\Param $param = null;

    private bool $empty = true;

    private ?string $pk = null;

    /**
     * @param int|\VV\Db\Param|null $type
     * @param int|null              $size
     * @param string|null           $pk
     *
     * @return $this
     */
    public function set($type = null, int $size = null, string $pk = null): self {
        $this->empty = false;

        if ($type) {
            if ($type instanceof \VV\Db\Param) {
                $param = $type;
            } else {
                $param = new \VV\Db\Param($type, null, null, $size);
            }
            if ($size !== null) $param->setSize($size);

            $this->param = $param;
        }
        $this->pk = $pk;

        return $this;
    }

    /**
     * @return mixed
     */
    public function value() {
        if (!$this->param) throw new \InvalidArgumentException('Param is empty');

        return $this->param->value();
    }

    /**
     * @return \VV\Db\Param
     */
    public function param() {
        return $this->param;
    }

    /**
     * @return string
     */
    public function pk(): ?string {
        return $this->pk;
    }

    /**
     * @inheritdoc
     */
    public function isEmpty(): bool {
        return $this->empty;
    }
}
