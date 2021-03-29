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

use VV\Db\Sql\Expression;

/**
 * Class Item
 *
 * @package VV\Db\Sql\Clause\OrderBy
 */
class OrderByItem {

    /**
     * @var string|\VV\Db\Sql\Expression
     */
    private $column;

    /**
     * @var bool
     */
    private $desc;

    /**
     * @var bool
     */
    private $nullsLast;

    /**
     * Item constructor.
     *
     * @param \VV\Db\Sql\Expression|string $column
     * @param bool                         $desc
     * @param bool                         $nullsLast
     */
    public function __construct($column, $desc = null, $nullsLast = null) {
        $this->setColumn($column);
        switch (true) {
            /** @noinspection PhpMissingBreakStatementInspection */
            case 2 < $c = func_num_args():
                $this->setNullsLast($nullsLast);
            case 1 < $c:
                $this->setDesc($desc);
        }
    }


    /**
     * @return Expression
     */
    public function column() {
        return $this->column;
    }

    /**
     * @return boolean
     */
    public function isDesc() {
        return $this->desc;
    }

    /**
     * @param boolean $desc
     *
     * @return $this
     */
    public function setDesc($desc) {
        $this->desc = (bool)$desc;

        return $this;
    }

    /**
     * @return boolean
     */
    public function isNullsLast() {
        if ($this->nullsLast === null) {
            return $this->isDesc();
        }

        return $this->nullsLast;
    }

    /**
     * @param boolean $nullsLast
     *
     * @return $this
     */
    public function setNullsLast($nullsLast) {
        $this->nullsLast = (bool)$nullsLast;

        return $this;
    }

    /**
     * @param string|Expression $column
     *
     * @return $this
     */
    protected function setColumn($column) {
        $this->column = \VV\Db\Sql::expression($column);

        return $this;
    }

    public static function create($expr) {
        if (\VV\emt($expr)) throw new \InvalidArgumentException('Expression is empty');
        if ($expr instanceof Expression) {
            return new static($expr);
        }

        if (!is_scalar($expr)) return false;

        if (!preg_match('/^(-)? (.+?) (?:\s+(asc|desc))? (?:\s+nulls\s+(first|last))?$/xi', $expr, $m)) {
            return false;
        }

        $desc = $m[1] || strtolower($m[3] ?? '') == 'desc';
        $item = new static($m[2], $desc);
        if ($nulls = $m[4] ?? null) {
            $item->setNullsLast(strtolower($nulls) == 'last');
        }

        return $item;
    }
}
