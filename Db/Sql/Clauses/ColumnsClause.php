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

use VV\Db\Sql;
use VV\Db\Sql\Expressions\AliasableExpression;
use VV\Db\Sql\Expressions\DbObject;
use VV\Db\Sql\Expressions\Expression;
use VV\Db\Sql\Expressions\PlainSql;

/**
 * Class ColumnsClause
 *
 * @package VV\Db\Sql\Clauses
 */
class ColumnsClause extends ColumnList
{
    private ?TableClause $tableClause = null;
    private ?array $resultFields = null;
    private ?array $resultFieldsMap = null;

    /**
     * @return Expression[]
     */
    public function getItems(): array
    {
        return parent::getItems() ?: [DbObject::create('*')];
    }

    /**
     * @inheritDoc
     */
    public function isEmpty(): bool
    {
        return false; // can't be empty (*)
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
    public function getResultFields(): array
    {
        $rf = &$this->resultFields;
        if ($rf === null) {
            $rf = [];
            foreach ($this->getItems() as $col) {
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
                                throw new \LogicException("Can't get result fields: no table model for *");
                            }

                            $rf = array_merge($rf, $tbl->getFields()->getNames());
                            continue;
                        }
                    }
                } elseif ($col instanceof AliasableExpression) {
                    $a = $col->getAlias();
                }

                if (!$a) {
                    throw new \LogicException("Alias for field $col is not set");
                }

                $rf[] = $a;
            }
        }

        return $rf;
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
    public function getResultFieldsMap(): ?array
    {
        return $this->resultFieldsMap;
    }

    /**
     * @param array|null $resultFieldsMap
     *
     * @return $this|ColumnsClause
     */
    public function setResultFieldsMap(?array $resultFieldsMap): self
    {
        $this->resultFieldsMap = $resultFieldsMap;

        return $this;
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
}
