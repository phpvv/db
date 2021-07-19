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

use JetBrains\PhpStorm\Pure;
use VV\Db\Sql\Expressions\Expression;

/**
 * Class Item
 *
 * @package VV\Db\Sql\Clause\OrderBy
 */
class OrderByClauseItem
{

    private Expression $expression;
    private bool $desc;
    private ?bool $nullsLast;

    /**
     * Item constructor.
     *
     * @param string|\VV\Db\Sql\Expressions\Expression $expression
     * @param bool|null                                $desc
     * @param bool|null                                $nullsLast
     */
    public function __construct(string|Expression $expression, bool $desc = null, bool $nullsLast = null)
    {
        if (!$expression instanceof Expression) {
            if (!preg_match('/^(-)? (.+?) (?:\s+(asc|desc))? (?:\s+nulls\s+(first|last))?$/xi', $expression, $m)) {
                throw new \InvalidArgumentException('Wrong ORDER BY string');
            }

            if ($desc === null) {
                $desc = $m[1] || strtolower($m[3] ?? '') == 'desc';
            }
            if ($nullsLast === null && ($nulls = $m[4] ?? null)) {
                $nullsLast = strtolower($nulls) == 'last';
            }
        }

        $this->setExpression($expression)
            ->setDesc($desc ?? false)
            ->setNullsLast($nullsLast);
    }


    /**
     * @return Expression
     */
    public function expression(): Expression
    {
        return $this->expression;
    }

    /**
     * @return bool
     */
    public function isDesc(): bool
    {
        return $this->desc;
    }

    /**
     * @param bool $desc
     *
     * @return $this
     */
    public function setDesc(bool $desc): static
    {
        $this->desc = $desc;

        return $this;
    }

    /**
     * @return bool
     */
    public function isNullsLast(): bool
    {
        if ($this->nullsLast === null) {
            return $this->isDesc();
        }

        return $this->nullsLast;
    }

    /**
     * @param bool|null $nullsLast
     *
     * @return $this
     */
    public function setNullsLast(?bool $nullsLast): static
    {
        $this->nullsLast = $nullsLast;

        return $this;
    }

    /**
     * @param string|Expression $expression
     *
     * @return $this
     */
    protected function setExpression(string|Expression $expression): static
    {
        $this->expression = \VV\Db\Sql::expression($expression);

        return $this;
    }

    /**
     * @param string|Expression $expression
     *
     * @return static|null
     */
    public static function create(string|Expression $expression): ?static
    {
        if (!$expression) {
            throw new \InvalidArgumentException('Expression is empty');
        }
        if ($expression instanceof Expression) {
            return new static($expression);
        }

        if (!is_scalar($expression)) {
            return null;
        }

        if (!preg_match('/^(-)? (.+?) (?:\s+(asc|desc))? (?:\s+nulls\s+(first|last))?$/xi', $expression, $m)) {
            return null;
        }

        $desc = $m[1] || strtolower($m[3] ?? '') == 'desc';
        $item = new static($m[2], $desc);
        if ($nulls = $m[4] ?? null) {
            $item->setNullsLast(strtolower($nulls) == 'last');
        }

        return $item;
    }
}
