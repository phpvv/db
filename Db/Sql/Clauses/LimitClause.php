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
 * Class Limit
 *
 * @package VV\Db\Sql\Clause
 */
class LimitClause implements Clause {

    /**
     * @var int
     */
    private $count = 0;

    /**
     * @var int
     */
    private $offset;

    /**
     * @return int
     */
    public function count() {
        return $this->count;
    }

    /**
     * @return int
     */
    public function offset() {
        return $this->offset;
    }

    /**
     * @param int $count
     * @param int $offset
     */
    public function set($count, $offset = 0) {
        $this->count = (int)$count;
        $this->offset = (int)$offset;
    }

    public function isEmpty(): bool {
        return !$this->count();
    }
}
