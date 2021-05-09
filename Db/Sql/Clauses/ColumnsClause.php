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

use VV\Db\Sql;

/**
 * Class ColumnsClause
 *
 * @package VV\Db\Sql\Clauses
 */
class ColumnsClause extends ColumnList {

    private ?TableClause $tableClause = null;
    private ?array $resultFields = null;
    private ?array $resultFieldsMap = null;

    /**
     * @return Sql\Expression[]
     */
    public function items(): array {
        return parent::items() ?: [Sql\DbObject::create('*')];
    }

    /**
     * @inheritDoc
     */
    public function isEmpty(): bool {
        return false; // can't be empty (*)
    }

    /**
     * Return true if `SELECT *`
     *
     * @return bool
     */
    public function isAllColumns(): bool {
        return !parent::items();
    }

    /**
     * @return string[]
     */
    public function resultFields(): array {
        $rf = &$this->resultFields;
        if ($rf === null) {
            $rf = [];
            foreach ($this->items() as $col) {
                $a = null;

                if ($col instanceof \VV\Db\Sql\DbObject) {
                    if (!$a = $col->alias()) {
                        $a = $col->name();
                        if ($a == '*') {
                            $tableClause = $this->tableClause();
                            if (!$tableClause) throw new \LogicException('Table clause is not set');

                            $owner = ($o = $col->owner()) ? $o->name() : null;
                            $tbl = $tableClause->tableModelOrMain($owner);
                            if (!$tbl) throw new \LogicException("Can't get result fields: no table model for *");

                            $rf = array_merge($rf, $tbl->fields()->names());
                            continue;
                        }
                    }
                } elseif ($col instanceof \VV\Db\Sql\Aliasable) {
                    $a = $col->alias();
                }

                if (!$a) throw new \LogicException("Alias for field $col is not set");

                $rf[] = $a;
            }
        }

        return $rf;
    }

    /**
     * @return TableClause|null
     */
    public function tableClause(): ?TableClause {
        return $this->tableClause;
    }

    /**
     * @param TableClause $tableClause
     *
     * @return $this
     */
    public function setTableClause(TableClause $tableClause): static {
        $this->tableClause = $tableClause;

        return $this;
    }

    /**
     * @return array|null
     */
    public function resultFieldsMap(): ?array {
        return $this->resultFieldsMap;
    }

    /**
     * @param array|null $resultFieldsMap
     *
     * @return $this|ColumnsClause
     */
    public function setResultFieldsMap(?array $resultFieldsMap): self {
        $this->resultFieldsMap = $resultFieldsMap;

        return $this;
    }

    protected function _add(array $columns) {
        foreach ($columns as $col) {
            $expr = Sql::expression($col);
            if ($expr instanceof Sql\Plain) {
                if ($alias = Sql\DbObject::parseAlias($expr->sql(), $sql)) {
                    $expr = Sql::plain($sql, $expr->params())
                        ->as($alias);
                }
            }

            $this->appendItems($expr);
        }
    }

    /**
     * @return array
     */
    protected function allowedObjectTypes(): array {
        return [Sql\Expression::class];
    }
}
