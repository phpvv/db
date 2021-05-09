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

use VV\Db\Sql\Condition\Predicate;
use VV\Db\Sql\Condition\Predicates;
use VV\Db\Sql\Condition\Predicates\Compare as Cmp;
use VV\Db\Sql\Expressions\Expression;

/**
 * Class Condition
 *
 * @package VV\Db\Sql
 *
 * @property-read Condition $and
 * @property-read Condition $or
 * @property-read Condition $not
 *
 * @method Condition\Item[] items():array
 */
class Condition extends \VV\Db\Sql\Clauses\ItemList implements Predicate {

    private ?string $connector = null;
    private ?Expression $target = null;
    private bool $itemNegation = false;
    /** Negation for all condition like in predicate */
    private bool $negation = false;

    public function __get($var) {
        return match ($var) {
            'and' => $this->and(),
            'or' => $this->or(),
            'not' => $this->not(),
            default => throw new \LogicException("Undefined property $var"),
        };
    }

    /**
     * @param Expression|string $expr
     *
     * @return $this
     */
    public function expr(string|Expression $expr): static {
        return $this->setTarget($expr);
    }

    /**
     * @param bool $flag
     *
     * @return $this
     */
    public function not(bool $flag = true): static {
        return $this->setItemNegation($flag);
    }

    /**
     * @param Expression|string|null $expr
     *
     * @return static
     */
    public function sub($expr = null): static {
        $sub = new self;

        if (!$expr) $expr = $this->target();
        if ($expr) $sub->expr($expr);

        $this->addPredicItem($sub);

        return $sub;
    }

    /**
     * @param array $and
     *
     * @return $this
     */
    public function subAnd(array $and): static {
        $this->sub()->and($and);

        return $this;
    }

    /**
     * @param array $or
     *
     * @return $this
     */
    public function subOr(array $or): static {
        $this->sub()->or($or);

        return $this;
    }

    /**
     * @param mixed       $param
     * @param string|null $operator
     *
     * @return $this
     */
    public function compare(mixed $param, string $operator = null): static {
        if ($operator === null) {
            $operator = Cmp::OP_EQ;
        }

        // check for "is (not) null"
        if (\VV\emt($param))
            switch ($operator) {
                case Cmp::OP_EQ:
                    return $this->isNull();
                case Cmp::OP_NE:
                case Cmp::OP_NE_ALT:
                    return $this->isNotNull();
            }

        $predic = new Cmp($this->target(), self::conv2param($param), $operator, $this->isItemNegation());

        return $this->_addPredicItem($predic);
    }

    /**
     * @param mixed $param
     *
     * @return $this
     */
    public function eq(mixed $param): static {
        return $this->compare($param, Cmp::OP_EQ);
    }

    /**
     * @param mixed $param
     *
     * @return $this
     */
    public function ne(mixed $param): static {
        return $this->compare($param, Cmp::OP_NE);
    }

    /**
     * @param mixed $param
     *
     * @return $this
     */
    public function lt(mixed $param): static {
        return $this->compare($param, Cmp::OP_LT);
    }

    /**
     * @param mixed $param
     *
     * @return $this
     */
    public function lte(mixed $param): static {
        return $this->compare($param, Cmp::OP_LTE);
    }

    /**
     * @param mixed $param
     *
     * @return $this
     */
    public function gt(mixed $param): static {
        return $this->compare($param, Cmp::OP_GT);
    }

    /**
     * @param mixed $param
     *
     * @return $this
     */
    public function gte(mixed $param): static {
        return $this->compare($param, Cmp::OP_GTE);
    }

    /**
     * @param $from
     * @param $till
     *
     * @return $this
     */
    public function between($from, $till): static {
        $predic = new Predicates\Between(
            $this->target(),
            self::conv2param($from),
            self::conv2param($till),
            $this->isItemNegation()
        );

        return $this->_addPredicItem($predic);
    }

    /**
     * @param array $params
     *
     * @return $this
     */
    public function in(...$params): static {
        if (count($params) && is_array($params[0])) $params = $params[0];

        $notNullParams = [];
        $containNull = false;
        foreach ($params as $p) {
            if (\VV\emt($p)) {
                $containNull = true;
            } else $notNullParams[] = $p;
        }

        $target = $this->target();
        $parentNot = $this->isItemNegation();

        $inPredic = $notNullParams
            ? new Predicates\In($target, $notNullParams, $parentNot)
            : null;

        // IN     (null, 1, 2) ==> IN (1, 2)     OR IS NULL
        // IN     (null)       ==>                  IS NULL
        // NOT IN       (1, 2) ==> NOT IN (1, 2) OR IS NULL
        if ($containNull == !$parentNot) {
            $sub = new self;
            if ($inPredic) $sub->_addPredicItem($inPredic);
            $sub->or($target)->isNull();

            return $this->_addPredicItem($sub);
        }

        // if $notNullParams is empty
        if (!$inPredic) {
            // NOT IN (null) ==> NOT NULL
            if ($containNull) return $this->isNotNull();
            // NOT IN () ==> no condition (all records)
            if ($parentNot) return $this;
            // NO RECORDS
            $inPredic = new Predicates\In($target, [\VV\Db\Sql::plain('NULL')]);
        }

        return $this->_addPredicItem($inPredic);
    }

    /**
     * @return $this
     */
    public function isNull(): static {
        $predic = new Predicates\IsNull($this->target(), $this->isItemNegation());

        return $this->_addPredicItem($predic);
    }

    /**
     * @return $this
     */
    public function isNotNull(): static {
        return $this->not()->isNull();
    }

    /**
     * @param mixed $pattern
     * @param bool  $caseInsensitive
     *
     * @return $this
     */
    public function like(string $pattern, bool $caseInsensitive = false): static {
        return $this->_addPredicItem(new Predicates\Like(
            $this->target(),
            self::conv2param($pattern),
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
    public function startsWith(string $prefix, bool $caseInsensitive = false): static {
        return $this->like(self::escape4like($prefix) . '%', $caseInsensitive);
    }

    /**
     * @param string $suffix
     * @param bool   $caseInsensitive
     *
     * @return $this
     */
    public function endsWith(string $suffix, bool $caseInsensitive = false): static {
        return $this->like('%' . self::escape4like($suffix), $caseInsensitive);
    }

    /**
     * @param string $string
     * @param bool   $caseInsensitive
     *
     * @return $this
     */
    public function contains(string $string, bool $caseInsensitive = false): static {
        return $this->like('%' . self::escape4like($string) . '%', $caseInsensitive);
    }

    /**
     * @param array $params
     *
     * @return $this
     */
    public function custom(...$params): static {
        if ($params && is_array($params[0])) $params = $params[0];
        $predic = new Predicates\Custom($this->target(), $params, $this->isItemNegation());

        return $this->addPredicItem($predic);
    }

    /**
     * @param \VV\Db\Sql\SelectQuery $query
     *
     * @return $this
     */
    public function exists(\VV\Db\Sql\SelectQuery $query): static {
        $predic = new Predicates\Exists($query, $this->isItemNegation());

        return $this->addPredicItem($predic);
    }

    /**
     * @param Predicate   $predic
     * @param string|null $connector
     *
     * @return $this
     */
    public function addPredicItem(Predicate $predic, string $connector = null): static {
        return $this->clearTarget()->_addPredicItem($predic, $connector);
    }

    /**
     * @param Condition\Item $item
     *
     * @return $this
     */
    public function addItem(Condition\Item $item): static {
        $this->clearTarget()->_addItem($item);

        return $this;
    }

    /**
     * @param Predicate   $predic
     * @param string|null $connector
     *
     * @return Condition\Item
     */
    public function createItem(Predicate $predic, string $connector = null): Condition\Item {
        return new Condition\Item($predic, $connector);
    }

    /**
     * @return Expression|null
     */
    public function target(): ?Expression {
        return $this->target;
    }

    /**
     * @return string|null
     */
    public function connector(): ?string {
        return $this->connector;
    }

    /**
     * @param boolean $itemNegation
     *
     * @return $this
     */
    public function setItemNegation(bool $itemNegation): static {
        $this->itemNegation = $itemNegation;

        return $this;
    }

    /**
     * @return bool
     */
    public function isNegative(): bool {
        return $this->negation;
    }

    /**
     * @param bool $negation
     *
     * @return $this
     */
    public function setNegation(bool $negation): static {
        $this->negation = $negation;

        return $this;
    }

    /**
     * @param Expression|string|null $target
     * @param Expression|array|mixed ...$params
     *
     * @return $this
     */
    public function and($target = null, ...$params): static {
        return $this->append(Condition\Item::CONN_AND, ...func_get_args());
    }

    /**
     * @param Expression|string|null $target
     * @param Expression|array|mixed ...$params
     *
     * @return $this
     */
    public function or($target = null, ...$params): static {
        return $this->append(Condition\Item::CONN_OR, ...func_get_args());
    }

    /**
     * @param Predicate   $predic
     * @param string|null $connector
     *
     * @return $this
     */
    protected function _addPredicItem(Predicate $predic, string $connector = null): static {
        if (!$connector) $connector = $this->connector();

        return $this->_addItem($this->createItem($predic, $connector));
    }

    /**
     * @param Condition\Item $item
     *
     * @return $this
     */
    protected function _addItem(Condition\Item $item): static {
        $this->setItemNegation(false);

        // for backward compatibility (replace same condition)
        $predic = $item->predicate();
        $itemKey = null;
        if ($predic instanceof Cmp && $item->connector() == Condition\Item::CONN_AND) {
            $itemKey = $predic->leftExpr()->exprId() . ' ' . $predic->operator();
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
    protected function setConnector(string $connector): static {
        $this->connector = $connector;

        return $this;
    }

    /**
     * @param Expression|string $target
     *
     * @return $this
     */
    protected function setTarget(Expression|string $target): static {
        if (!$target) throw new \InvalidArgumentException('Expression is empty');

        $this->target = self::conv2expr($target);

        return $this;
    }

    /**
     * @return $this
     */
    protected function clearTarget(): static {
        $this->target = null;

        return $this;
    }

    /**
     * @return bool
     */
    protected function isItemNegation(): bool {
        return $this->itemNegation;
    }

    /**
     * @param string                     $connector
     * @param Expression|string|null     $target
     * @param Expression[]|array|mixed[] ...$params
     *
     * @return $this
     */
    private function append(string $connector, $target = null, ...$params): static {
        $this->setConnector($connector);

        // if nothing passed, just set connector (traget is previous)
        if (1 >= $c = func_num_args()) return $this;

        // process as cycle call of and()/or()
        if (is_array($target)) {
            foreach ($target as $k => $v) {
                if (is_int($k)) {
                    if (is_string($v)) {
                        $this->setTarget($v)->custom();
                    } elseif ($v instanceof Predicate) {
                        $this->addPredicItem($v);
                    } else {
                        throw new \LogicException('Unsupported predicate');
                    }
                } elseif (is_array($v)) {
                    $this->setTarget($k)->custom(...$v);
                } else {
                    $this->append($connector, $k, $v);
                }
            }

            return $this;
        }

        if ($target instanceof Predicate) {
            return $this->addPredicItem($target);
        }

        if ($c < 3) {
            return $this->setTarget($target);
        }

        if (is_array($params[0]) && is_string($target)) {
            return $this->setTarget($target)->custom(...$params[0]);
        }

        // try to parse
        if (is_array($params[0])) {
            $params = $params[0];
        }

        $equals = is_object($target);
        if (!$equals && $obj = Expressions\DbObject::create($target, null, false)) {
            $target = $obj;
            $equals = true;
        }

        if (!$equals && is_string($target)) {
            $operatorAccepted =
                $this->parseCompareTarget($target, $params)
                || $this->parseLikeTarget($target, $params)
                || $this->parseInTarget($target, $params)
                || $this->parseIsNullTarget($target, $params)
                || $this->parseBetweenTarget($target, $params);

            if ($operatorAccepted) {
                return $this;
            }
        }

        $this->throwIfWrongParamsCount($params, 1)
            ->setTarget($target)
            ->eq(...$params);

        return $this;
    }

    /**
     * @param string $target
     * @param array  $params
     *
     * @return $this|null
     */
    private function parseCompareTarget(string $target, array $params): ?self {
        if (!$this->parseTargetOperator($target, '= | != | <> | < | > | <= | >=', $m)) {
            return null;
        }

        return $this
            ->throwIfWrongParamsCount($params, 1)
            ->compare($params[0], $m[2]);
    }

    /**
     * @param string $target
     * @param array  $params
     *
     * @return $this|null
     */
    private function parseLikeTarget(string $target, array $params): ?self {
        if (!$this->parseTargetOperator($target, '(not \s*)? like')) {
            return null;
        }

        return $this
            ->throwIfWrongParamsCount($params, 1)
            ->like($params[0]);
    }

    /**
     * @param string $target
     * @param array  $params
     *
     * @return $this|null
     */
    private function parseBetweenTarget(string $target, array $params): ?self {
        if (!$this->parseTargetOperator($target, '(not \s*)? between')) {
            return null;
        }

        return $this
            ->throwIfWrongParamsCount($params, 2)
            ->between(...$params);
    }

    /**
     * @param string $target
     * @param array  $params
     *
     * @return $this|null
     */
    private function parseInTarget(string $target, array $params): ?self {
        if (!$this->parseTargetOperator($target, '(not \s*)? in')) {
            return null;
        }

        return $this->in(...$params);
    }

    /**
     * @param string $target
     * @param array  $params
     *
     * @return $this|null
     */
    private function parseIsNullTarget(string $target, array $params): ?self {
        if (!$this->parseTargetOperator($target, 'is (\s* not)?')) {
            return null;
        }

        return $this
            ->throwIfWrongParamsCount($params, 0)
            ->isNull();
    }

    /**
     * @param string $str
     * @param string $operatorRx
     * @param null   $matches
     *
     * @return $this|null
     */
    private function parseTargetOperator(string $str, string $operatorRx, &$matches = null): ?self {
        if (!preg_match('/^(.+?) \s* (' . $operatorRx . ') \s*$/xi', $str, $matches)) {
            return null;
        }

        return $this
            ->setTarget($matches[1])
            ->setItemNegation(count($matches) > 3);
    }

    /**
     * @param array $params
     * @param int   $count
     *
     * @return $this
     */
    private function throwIfWrongParamsCount(array $params, int $count): static {
        if ($count != $realCount = count($params)) {
            throw new \InvalidArgumentException("Method expects $count parameters, $realCount given");
        }

        return $this;
    }

    /**
     * @param $obj
     *
     * @return Expression
     */
    public static function conv2expr($obj): Expression {
        return \VV\Db\Sql::expression($obj);
    }

    /**
     * @param $obj
     *
     * @return Expression
     */
    public static function conv2param($obj): Expression {
        return \VV\Db\Sql::param($obj);
    }

    /**
     * @param string $str
     *
     * @return string
     */
    public static function escape4like(string $str): string {
        return preg_replace('![_%\\\\]!', '\\$0', $str);
    }
}
