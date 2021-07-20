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
 * Class LimitClause
 *
 * @package VV\Db\Sql\Clauses
 */
class LimitClause implements Clause
{
    private int $count = 0;
    private int $offset = 0;

    /**
     * @return int
     */
    public function count(): int
    {
        return $this->count;
    }

    /**
     * @return int
     */
    public function offset(): int
    {
        return $this->offset;
    }

    /**
     * @param int $count
     * @param int $offset
     */
    public function set(int $count, int $offset = 0)
    {
        $this->count = $count;
        $this->offset = $offset;
    }

    /**
     * @return bool
     */
    public function isEmpty(): bool
    {
        return !$this->count();
    }
}
