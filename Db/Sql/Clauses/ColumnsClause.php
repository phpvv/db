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

use VV\Db\Model\Table;
use VV\Db\Sql;
use VV\Db\Sql\Condition;
use VV\Db\Sql\Expressions\AliasableExpression;
use VV\Db\Sql\Expressions\DbObject;
use VV\Db\Sql\Expressions\Expression;
use VV\Db\Sql\Expressions\PlainSql;
use VV\Db\Sql\SelectQuery;

/**
 * Class ColumnsClause
 *
 * @package VV\Db\Sql\Clauses
 */
class ColumnsClause extends ColumnList
{
    protected const MAX_RESULT_COLUMN_NAME_LENGTH = 30;

    private ?TableClause $tableClause = null;
    private ?array $resultColumns = null;
    private ?array $resultColumnsMap = null;
    private bool $asteriskOnEmpty = true;

    /**
     * @param bool $asteriskOnEmpty
     *
     * @return $this
     */
    public function setAsteriskOnEmpty(bool $asteriskOnEmpty): static
    {
        $this->asteriskOnEmpty = $asteriskOnEmpty;

        return $this;
    }

    /**
     * @return Expression[]
     */
    public function getItems(): array
    {
        return parent::getItems() ?: ($this->asteriskOnEmpty ? [DbObject::create('*')] : []);
    }

    /**
     * @inheritDoc
     */
    public function isEmpty(): bool
    {
        return !$this->asteriskOnEmpty && parent::isEmpty();
    }

    /**
     * Return true if `SELECT *`
     *
     * @return bool
     */
    public function isAsterisk(): bool
    {
        return !parent::getItems();
    }

    /**
     * @return string[]
     */
    public function getResultColumns(): array
    {
        $columns = &$this->resultColumns;
        if ($columns === null) {
            $columns = [];
            foreach ($this->getItems() as $i => $col) {
                $a = null;

                if ($col instanceof DbObject) {
                    if (!$a = $col->getAlias()) {
                        $a = $col->getName();
                        if ($a == '*') {
                            $tableClause = $this->getTableClause();
                            if (!$tableClause) {
                                throw new \LogicException('Table clause is not set');
                            }

                            $owner = ($o = $col->getOwner()) ? $o->getName() : null;
                            $tbl = $tableClause->getTableModelOrMain($owner);
                            if (!$tbl) {
                                throw new \LogicException("Can't get result column name: no table model for *");
                            }

                            $columns = array_merge($columns, $tbl->getColumns()->getNames());
                            continue;
                        }
                    }
                } elseif ($col instanceof AliasableExpression) {
                    $a = $col->getAlias();
                }

                if (!$a) {
                    throw new \LogicException("Alias for column #$i is not set");
                }

                $columns[] = $a;
            }
        }

        return $columns;
    }

    /**
     * @return TableClause|null
     */
    public function getTableClause(): ?TableClause
    {
        return $this->tableClause;
    }

    /**
     * @param TableClause|null $clause
     *
     * @return $this
     */
    public function setTableClause(?TableClause $clause): static
    {
        $this->tableClause = $clause;

        return $this;
    }

    /**
     * @return array|null
     */
    public function getResultColumnsMap(): ?array
    {
        return $this->resultColumnsMap;
    }

    /**
     * @param array|null $resultColumnsMap
     *
     * @return $this|ColumnsClause
     */
    public function setResultColumnsMap(?array $resultColumnsMap): self
    {
        $this->resultColumnsMap = $resultColumnsMap;

        return $this;
    }

    /**
     * Appends nested columns
     *
     * @param string|string[]       $path
     * @param string                $defaultTableAlias
     * @param string|int|Expression ...$columns
     *
     * @return $this
     */
    public function addNested(
        array|string $path,
        string $defaultTableAlias,
        string|int|Expression ...$columns
    ): static {
        if (!is_array($path)) {
            $path = [$path];
        }

        $map = &$this->resultColumnsMap;
        foreach ($columns as &$col) {
            if (is_string($col)) {
                $col = DbObject::create($col, $defaultTableAlias);
                $col->as($col->getResultName());
            }

            if (!$col instanceof Expression) {
                throw new \InvalidArgumentException('$column must be string or Sql\Expr');
            }

            $columnPath = $path;
            $columnPath[] = $col->getAlias();

            // build short alias name
            $sqlAlias = ColumnsClause::buildNestedColumnAlias($columnPath, $map);

            // add columnPath to map and save column alias
            $map[$sqlAlias] = $columnPath;
            $col->as($sqlAlias);
        }
        unset($col, $map);

        return $this->add(...$columns);
    }

    /**
     * Appends nested columns from another SelectQuery or Table
     *
     * @param Table|SelectQuery           $from
     * @param string|string[]             $path
     * @param string|null                 $alias
     *
     * @return $this
     */
    public function addNestedFrom(Table|SelectQuery $from, array|string $path = null, string $alias = null): static
    {
        if ($path === null) {
            $path = [$alias];
        }

        if (!is_array($path)) {
            $path = [$path];
        }

        if ($from instanceof Table) {
            return $this->addNested($path, $alias, ...$from->getColumns()->getNames());
        }

        $resultColumns = $from->getColumnsClause()->getResultColumns();

        if ($joinedMap = $from->getResultColumnsMap()) {
            $resultColumns = array_diff($resultColumns, array_keys($joinedMap));

            $map = $this->getResultColumnsMap();
            foreach ($joinedMap as $subColumn => $subPath) {
                $jPath = array_merge($path, $subPath);
                $sqlAlias = ColumnsClause::buildNestedColumnAlias($jPath, $map);
                $map[$sqlAlias] = $jPath;
                $this->add("$alias.$subColumn $sqlAlias");
            }

            $this->setResultColumnsMap($map);
        }

        return $this->addNested($path, $alias, ...$resultColumns);
    }

    /**
     * @inheritDoc
     */
    protected function addColumnArray(array $columns): void
    {
        foreach ($columns as $col) {
            $expr = Sql::expression($col);
            if ($expr instanceof PlainSql) {
                if ($alias = DbObject::parseAlias($expr->getSql(), $sql)) {
                    $expr = Sql::plain($sql, $expr->getParams())
                        ->as($alias);
                }
            }

            $this->appendItems($expr);
        }
    }

    /**
     * @inheritDoc
     */
    protected function getAllowedObjectTypes(): array
    {
        return [Expression::class];
    }

    /**
     * @param array      $path
     * @param array|null $resultColumnsMap
     *
     * @return string
     */
    public static function buildNestedColumnAlias(array $path, ?array $resultColumnsMap): string
    {
        $sqlAlias = '$' . implode('_', $path);
        $maxLength = static::MAX_RESULT_COLUMN_NAME_LENGTH;
        $len = strlen($sqlAlias);
        if ($len > $maxLength) {
            $sqlAlias = substr($sqlAlias, 0, $maxLength);
            $len = $maxLength;
        }

        $i = 1;
        if ($resultColumnsMap) {
            $d = $maxLength - $len;

            while (array_key_exists($sqlAlias, $resultColumnsMap)) {
                $sfx = '_' . $i++;
                $sfxLen = strlen($sfx);

                $cutAlias = $d < $sfxLen
                    ? substr($sqlAlias, 0, $maxLength - $sfxLen + $d)
                    : $sqlAlias;

                $sqlAlias = $cutAlias . $sfx;
            }
        }

        return $sqlAlias;
    }
}
