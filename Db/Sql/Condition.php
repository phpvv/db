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

namespace VV\Db\Sql;

use VV\Db\Sql;
use VV\Db\Sql\Clauses\ItemList;
use VV\Db\Sql\Expressions\DbObject;
use VV\Db\Sql\Expressions\Expression;
use VV\Db\Sql\Predicates\BetweenPredicate;
use VV\Db\Sql\Predicates\ComparePredicate as Cmp;
use VV\Db\Sql\Predicates\CustomPredicate;
use VV\Db\Sql\Predicates\ExistsPredicate;
use VV\Db\Sql\Predicates\InPredicate;
use VV\Db\Sql\Predicates\IsNullPredicate;
use VV\Db\Sql\Predicates\LikePredicate;
use VV\Db\Sql\Predicates\Predicate;

/**
 * Class Condition
 *
 * @package VV\Db\Sql
 *
 * @property-read Condition $and
 * @property-read Condition $or
 * @property-read Condition $not
 *
 * @method ConditionItem[] getItems(): array
 */
class Condition extends ItemList implements Predicate
{
    private ?string $connector = null;
    private ?Expression $target = null;
    private bool $itemNegation = false;
    /** Negation for all condition like in predicate */
    private bool $negation = false;

    public function __get($var)
    {
        return match ($var) {
            'and' => $this->and(),
            'or' => $this->or(),
            'not' => $this->not(),
        };
    }

    /** Sets expression (target) for next comparison method */
    public function expression(string|int|Expression $expression): static
    {
        return $this->setTarget($expression);
    }

    /**
     * @deprecated Use {@see \VV\Db\Sql\Condition::expression()}
     */
    public function expr(string|int|Expression $expression): static
    {
        return $this->setTarget($expression);
    }

    /** Sets negation for next comparison method */
    public function not(bool $flag = true): static
    {
        return $this->setItemNegation($flag);
    }

    /** Creates sub condition and adds it to current condition */
    public function sub(string|int|Expression $expression = null): static
    {
        $sub = new self();

        if (!$expression) {
            $expression = $this->getTarget();
        }
        if ($expression) {
            $sub->setTarget($expression);
        }

        $this->addPredicate($sub);

        return $sub;
    }

    /** Adds sub expression and applies $and array to `and()` of sub condition */
    public function subAnd(array $and): static
    {
        $this->sub()->and($and);

        return $this;
    }

    /** Adds sub expression and applies $or array to `or()` of sub condition */
    public function subOr(array $or): static
    {
        $this->sub()->or($or);

        return $this;
    }

    /**
     * @param mixed       $param
     * @param string|null $operator
     *
     * @return $this
     */
    public function compare(mixed $param, string $operator = null): static
    {
        if ($operator === null) {
            $operator = Cmp::OP_EQ;
        }

        // check for "is (not) null"
        if ($param === null) {
            switch ($operator) {
                case Cmp::OP_EQ:
                    return $this->isNull();
                case Cmp::OP_NE:
                case Cmp::OP_NE_ALT:
                    return $this->isNotNull();
            }
        }

        $predicate = new Cmp($this->getTarget(), self::toParam($param), $operator, $this->isItemNegation());

        return $this->plainAddPredicate($predicate);
    }

    /**
     * @param mixed $param
     *
     * @return $this
     */
    public function eq(mixed $param): static
    {
        return $this->compare($param, Cmp::OP_EQ);
    }

    /**
     * @param mixed $param
     *
     * @return $this
     */
    public function ne(mixed $param): static
    {
        return $this->compare($param, Cmp::OP_NE);
    }

    /**
     * @param mixed $param
     *
     * @return $this
     */
    public function lt(mixed $param): static
    {
        return $this->compare($param, Cmp::OP_LT);
    }

    /**
     * @param mixed $param
     *
     * @return $this
     */
    public function lte(mixed $param): static
    {
        return $this->compare($param, Cmp::OP_LTE);
    }

    /**
     * @param mixed $param
     *
     * @return $this
     */
    public function gt(mixed $param): static
    {
        return $this->compare($param, Cmp::OP_GT);
    }

    /**
     * @param mixed $param
     *
     * @return $this
     */
    public function gte(mixed $param): static
    {
        return $this->compare($param, Cmp::OP_GTE);
    }

    /**
     * @param mixed $from
     * @param mixed $till
     *
     * @return $this
     */
    public function between(mixed $from, mixed $till): static
    {
        $predicate = new BetweenPredicate(
            $this->getTarget(),
            self::toParam($from),
            self::toParam($till),
            $this->isItemNegation()
        );

        return $this->plainAddPredicate($predicate);
    }

    /**
     * @param array $params
     *
     * @return $this
     */
    public function in(mixed ...$params): static
    {
        if (count($params) && is_array($params[0])) {
            $params = $params[0];
        }

        $notNullParams = [];
        $containNull = false;
        foreach ($params as $p) {
            if ($p === null || $p === '') {
                $containNull = true;
            } else {
                $notNullParams[] = $p;
            }
        }

        $target = $this->getTarget();
        $parentNot = $this->isItemNegation();

        $inPredicate = $notNullParams
            ? new InPredicate($target, $notNullParams, $parentNot)
            : null;

        // IN     (null, 1, 2) ==> IN (1, 2)     OR IS NULL
        // IN     (null)       ==>                  IS NULL
        // NOT IN       (1, 2) ==> NOT IN (1, 2) OR IS NULL
        if ($containNull == !$parentNot) {
            $sub = new self();
            if ($inPredicate) {
                $sub->plainAddPredicate($inPredicate);
            }
            $sub->or($target)->isNull();

            return $this->plainAddPredicate($sub);
        }

        // if $notNullParams is empty
        if (!$inPredicate) {
            // NOT IN (null) ==> NOT NULL
            if ($containNull) {
                return $this->isNotNull();
            }
            // NOT IN () ==> no condition (all records)
            if ($parentNot) {
                return $this;
            }
            // NO RECORDS
            $inPredicate = new InPredicate($target, [Sql::plain('NULL')]);
        }

        return $this->plainAddPredicate($inPredicate);
    }

    /**
     * @return $this
     */
    public function isNull(): static
    {
        $predicate = new IsNullPredicate($this->getTarget(), $this->isItemNegation());

        return $this->plainAddPredicate($predicate);
    }

    /**
     * @return $this
     */
    public function isNotNull(): static
    {
        return $this->not()->isNull();
    }

    /**
     * @param mixed $pattern
     * @param bool  $caseInsensitive
     *
     * @return $this
     */
    public function like(string $pattern, bool $caseInsensitive = false): static
    {
        return $this->plainAddPredicate(new LikePredicate(
            $this->getTarget(),
            self::toParam($pattern),
            $this->isItemNegation(),
            $caseInsensitive
        ));
    }

    /**
     * @param string $prefix
     * @param bool   $caseInsensitive
     *
     * @return $this
     */
    public function startsWith(string $prefix, bool $caseInsensitive = false): static
    {
        return $this->like(self::escape4like($prefix) . '%', $caseInsensitive);
    }

    /**
     * @param string $suffix
     * @param bool   $caseInsensitive
     *
     * @return $this
     */
    public function endsWith(string $suffix, bool $caseInsensitive = false): static
    {
        return $this->like('%' . self::escape4like($suffix), $caseInsensitive);
    }

    /**
     * @param string $string
     * @param bool   $caseInsensitive
     *
     * @return $this
     */
    public function contains(string $string, bool $caseInsensitive = false): static
    {
        return $this->like('%' . self::escape4like($string) . '%', $caseInsensitive);
    }

    /**
     * @param array $params
     *
     * @return $this
     */
    public function custom(...$params): static
    {
        if ($params && is_array($params[0])) {
            $params = $params[0];
        }
        $predicate = new CustomPredicate($this->getTarget(), $params, $this->isItemNegation());

        return $this->addPredicate($predicate);
    }

    /**
     * @param SelectQuery $query
     *
     * @return $this
     */
    public function exists(SelectQuery $query): static
    {
        $predicate = new ExistsPredicate($query, $this->isItemNegation());

        return $this->addPredicate($predicate);
    }

    /**
     * @param Predicate   $predicate
     * @param string|null $connector
     *
     * @return $this
     */
    public function addPredicate(Predicate $predicate, string $connector = null): static
    {
        return $this->clearTarget()->plainAddPredicate($predicate, $connector);
    }

    /**
     * @param ConditionItem $item
     *
     * @return $this
     */
    public function addItem(ConditionItem $item): static
    {
        $this->clearTarget()->plainAddItem($item);

        return $this;
    }

    /**
     * @param Predicate   $predicate
     * @param string|null $connector
     *
     * @return ConditionItem
     */
    public function createItem(Predicate $predicate, string $connector = null): ConditionItem
    {
        return new ConditionItem($predicate, $connector);
    }

    /**
     * @return Expression|null
     */
    public function getTarget(): ?Expression
    {
        return $this->target;
    }

    /**
     * @return string|null
     */
    public function getConnector(): ?string
    {
        return $this->connector;
    }

    /**
     * @param bool $itemNegation
     *
     * @return $this
     */
    public function setItemNegation(bool $itemNegation): static
    {
        $this->itemNegation = $itemNegation;

        return $this;
    }

    /**
     * @return bool
     */
    public function isNegative(): bool
    {
        return $this->negation;
    }

    /**
     * @param bool $negation
     *
     * @return $this
     */
    public function setNegation(bool $negation): static
    {
        $this->negation = $negation;

        return $this;
    }

    /**
     * @param string|int|array|Expression|Predicate|null $target
     * @param mixed|null                                 $param
     *
     * @return $this
     */
    public function and(string|int|array|Expression|Predicate $target = null, mixed $param = null): static
    {
        return $this->append(ConditionItem::CONN_AND, ...func_get_args());
    }

    /**
     * @param string|int|array|Expression|Predicate|null $target
     * @param mixed                                      $param
     *
     * @return $this
     */
    public function or(string|int|array|Expression|Predicate $target = null, mixed $param = null): static
    {
        return $this->append(ConditionItem::CONN_OR, ...func_get_args());
    }

    /**
     * @param Predicate   $predicate
     * @param string|null $connector
     *
     * @return $this
     */
    protected function plainAddPredicate(Predicate $predicate, string $connector = null): static
    {
        if (!$connector) {
            $connector = $this->getConnector();
        }

        return $this->plainAddItem($this->createItem($predicate, $connector));
    }

    /**
     * @param ConditionItem $item
     *
     * @return $this
     */
    protected function plainAddItem(ConditionItem $item): static
    {
        $this->setItemNegation(false);

        // for backward compatibility (replace same condition)
        $predicate = $item->getPredicate();
        $itemKey = null;
        if ($predicate instanceof Cmp && $item->getConnector() == ConditionItem::CONN_AND) {
            $itemKey = $predicate->getLeftExpression()->getExpressionId() . ' ' . $predicate->getOperator();
        }

        if ($itemKey) {
            $this->items[$itemKey] = $item;
        } else {
            $this->items[] = $item;
        }

        return $this;
    }

    /**
     * @param string $connector
     *
     * @return $this
     */
    protected function setConnector(string $connector): static
    {
        $this->connector = $connector;

        return $this;
    }

    /**
     * @param Expression|string $target
     *
     * @return $this
     */
    protected function setTarget(Expression|string $target): static
    {
        if (!$target) {
            throw new \InvalidArgumentException('Expression is empty');
        }

        $this->target = self::toExpression($target);

        return $this;
    }

    /**
     * @return $this
     */
    protected function clearTarget(): static
    {
        $this->target = null;

        return $this;
    }

    /**
     * @return bool
     */
    protected function isItemNegation(): bool
    {
        return $this->itemNegation;
    }

    /**
     * @param string                                     $connector
     * @param string|int|array|Expression|Predicate|null $target
     * @param mixed                                      $param
     *
     * @return $this
     */
    private function append(
        string $connector,
        string|int|array|Expression|Predicate $target = null,
        mixed $param = null
    ): static {
        $this->setConnector($connector);

        // if nothing passed, just set connector (target is previous)
        if (1 >= $argc = func_num_args()) {
            return $this;
        }

        // process as cycle call of and()/or()
        if (is_array($target)) {
            foreach ($target as $k => $v) {
                if (is_int($k)) {
                    if (is_string($v)) {
                        $this->setTarget($v)->custom();
                    } elseif ($v instanceof Predicate) {
                        $this->addPredicate($v);
                    } else {
                        throw new \LogicException('Unsupported predicate');
                    }
                } else {
                    $this->appendTargetParam($k, $v);
                }
            }

            return $this;
        }

        // target - is complete condition/predicate
        if ($target instanceof Predicate) {
            return $this->addPredicate($target);
        }

        // if no params argument - just leave target set (common case)
        // further call of predicate acceptance method (`eq()`, `in()`, `like()`...) is required
        if ($argc < 3) {
            return $this->setTarget($target);
        }

        return $this->appendTargetParam($target, $param);
    }

    private function appendTargetParam(string|int|Expression $target, mixed $param): static
    {
        // if $param is array - add custom condition
        if (is_array($param)) {
            return $this->setTarget($target)->custom(...$param);
        }

        // Expression - only for equals
        if ($target instanceof Expression) {
            return $this->setTarget($target)->eq($param);
        }

        if (!is_string($target)) {
            throw new \InvalidArgumentException('Invalid $target');
        }
        if (is_array($param)) {
            throw new \InvalidArgumentException('Invalid $param');
        }

        // $target is column (table.column)
        if ($dbObject = DbObject::create($target, null, false)) {
            return $this->setTarget($dbObject)->eq($param);
        }

        // parse comparison operator
        if (preg_match('/^(.+?) \s* (= | != | <> | < | > | <= | >=) \s*$/xi', $target, $m)) {
            return $this->setTarget($m[1])->compare($param, $m[2]);
        }

        throw new \InvalidArgumentException('Invalid $target');
    }

    /**
     * @param string|int|Expression $expression
     *
     * @return Expression
     */
    public static function toExpression(string|int|Expression $expression): Expression
    {
        return Sql::expression($expression, parseAlias: false);
    }

    /**
     * @param mixed $param
     *
     * @return Expression
     */
    public static function toParam(mixed $param): Expression
    {
        return Sql::param($param);
    }

    /**
     * @param string $str
     *
     * @return string
     */
    public static function escape4like(string $str): string
    {
        return preg_replace('![_%\\\\]!', '\\$0', $str);
    }
}
