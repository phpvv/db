<?php declare(strict_types=1);
/** @noinspection PhpUnnecessaryFullyQualifiedNameInspection */

/*
 * This file is part of the VV package.
 *
 * (c) Volodymyr Sarnytskyi <v00v4n@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
namespace VV\Db\Sql\Clauses;

use VV\Db\Model\Table as TableModel;
use VV\Db\Sql\Condition\Condition;
use VV\Db\Sql\Expressions\DbObject as SqlObj;
use VV\Db\Sql\Expressions\Expression as SqlExpr;

/**
 * Class Table
 *
 * @package VV\Db\Sql\Clauses
 * @method TableClauseItem[] items():array
 */
class TableClause extends ItemList {

    private ?TableClauseItem $mainItem = null;
    private ?TableClauseItem $lastItem = null;

    /**
     * @param string $alias
     *
     * @return TableClauseItem
     */
    public function item(string $alias): TableClauseItem {
        if (!$this->hasItem($alias)) throw new \OutOfBoundsException("Item with alias '$alias' not found");

        return $this->items()[$alias];
    }

    /**
     * @return TableClauseItem
     */
    public function mainItem(): TableClauseItem {
        if (!$this->mainItem) throw new \LogicException('Main Item is not defined yet');

        return $this->mainItem;
    }

    /**
     * @return TableClauseItem
     */
    public function lastItem(): TableClauseItem {
        if (!$this->lastItem) throw new \LogicException('Last Item is not defined yet');

        return $this->lastItem;
    }

    /**
     * @param string $alias
     *
     * @return bool
     */
    public function hasItem(string $alias): bool {
        return isset($this->items[$alias]);
    }

    /**
     * @param string $alias
     *
     * @return TableClauseItem
     */
    public function itemOrMain(string $alias): TableClauseItem {
        return $alias ? $this->item($alias) : $this->mainItem();
    }

    /**
     * @param string $alias
     *
     * @return TableClauseItem
     */
    public function itemOrLast(string $alias): TableClauseItem {
        return $alias ? $this->item($alias) : $this->lastItem();
    }

    /**
     * Returns table with alias == $alias
     *
     * @param string $alias
     *
     * @return TableModel|null
     */
    public function tableModel(string $alias): ?TableModel {
        return $this->item($alias)->tableModel();
    }

    /**
     * @return TableModel|null
     */
    public function mainTableModel(): ?TableModel {
        return $this->mainItem()->tableModel();
    }

    /**
     * @return TableModel|null
     */
    public function lastTableModel(): ?TableModel {
        return $this->lastItem()->tableModel();
    }

    /**
     * Returns table with alias == $alias
     *
     * @param string|null $alias
     *
     * @return TableModel|null
     */
    public function tableModelOrMain(?string $alias): ?TableModel {
        return $alias
            ? $this->tableModel($alias)
            : $this->mainTableModel();
    }

    /**
     * Returns table with alias == $alias
     *
     * @param string $alias
     *
     * @return TableModel|null
     */
    public function tableModelOrLast(string $alias): ?TableModel {
        return $alias
            ? $this->tableModel($alias)
            : $this->lastTableModel();
    }

    /**
     * @return string|null
     */
    public function mainTableAlias(): ?string {
        return $this->mainItem()->table()->alias();
    }

    /**
     * @return string|null
     */
    public function lastTableAlias(): ?string {
        return $this->lastItem()->table()->alias();
    }

    /**
     * Returns PK of table with alias == $alias
     *
     * @param string $alias
     *
     * @return string|null
     */
    public function tablePk(string $alias): ?string {
        return ($mtm = $this->tableModel($alias)) ? $mtm->pk() : null;
    }

    /**
     * Returns PK of main table with alias == $alias
     *
     * @return string|null
     */
    public function mainTablePk(): ?string {
        return ($mtm = $this->mainTableModel()) ? $mtm->pk() : null;
    }

    /**
     * Returns PK of last table with alias == $alias
     *
     * @return string|null
     */
    public function lastTablePk(): ?string {
        return ($mtm = $this->lastTableModel()) ? $mtm->pk() : null;
    }

    /**
     * Returns name of Primary Key field of table with alias == $alias
     *
     * @param string $alias
     *
     * @return string|null If table is not instance of \VV\Db\Tbl, returns null.
     */
    public function pkByAlias(string $alias): ?string {
        return ($tblMdl = $this->tableModel($alias)) ? $tblMdl->pk() : null;
    }

    public function main($tbl, $alias = null): static {
        $this->items = [];
        $this->mainItem = null;
        $this->_add($tbl, null, $alias);

        return $this;
    }

    /**
     * Adds (INNER) JOIN clause
     *
     * @param string|TableModel|SqlExpr $tbl
     * @param null                      $on
     * @param null                      $alias
     *
     * @return $this
     */
    public function join(string|TableModel|SqlExpr $tbl, $on = null, $alias = null): static {
        return $this->_join($tbl, $on, $alias);
    }

    /**
     * Adds LEFT JOIN clause
     *
     * @param string|TableModel|SqlExpr $tbl
     * @param null                      $on
     * @param null                      $alias
     *
     * @return $this
     */
    public function left(string|TableModel|SqlExpr $tbl, $on = null, $alias = null): static {
        return $this->_join($tbl, $on, $alias, TableClauseItem::J_LEFT);
    }

    /**
     * Adds RIGHT JOIN clause
     *
     * @param string|TableModel|SqlExpr $tbl
     * @param null                      $on
     * @param null                      $alias
     *
     * @return $this
     */
    public function right(string|TableModel|SqlExpr $tbl, $on = null, $alias = null): static {
        return $this->_join($tbl, $on, $alias, TableClauseItem::J_RIGHT);
    }

    /**
     * Adds FULL JOIN clause
     *
     * @param string|TableModel|SqlExpr $tbl
     * @param null                      $on
     * @param null                      $alias
     *
     * @return $this
     */
    public function full(string|TableModel|SqlExpr $tbl, $on = null, $alias = null): static {
        return $this->_join($tbl, $on, $alias, TableClauseItem::J_FULL);
    }

    //endregion
    //region join - back

    /**
     * Adds (INNER) JOIN clause backward
     *
     * @param string|TableModel|SqlExpr $tbl
     * @param null                      $ontbl
     * @param null                      $alias
     *
     * @return $this
     */
    public function joinBack(string|TableModel|SqlExpr $tbl, $ontbl = null, $alias = null): static {
        return $this->_joinBack($tbl, $ontbl, $alias);
    }

    /**
     * Adds LEFT JOIN clause backward
     *
     * @param string|TableModel|SqlExpr $tbl
     * @param null                      $ontbl
     * @param null                      $alias
     *
     * @return $this
     */
    public function leftBack(string|TableModel|SqlExpr $tbl, $ontbl = null, $alias = null): static {
        return $this->_joinBack($tbl, $ontbl, $alias, TableClauseItem::J_LEFT);
    }

    //endregion
    //region join - parent

    /**
     * Adds (INNER) JOIN on same table by parent-field
     *
     * @param string $alias
     * @param null   $ontbl
     * @param null   $parent_field Default - "parent_id"
     *
     * @return $this
     */
    public function joinParent(string $alias, $ontbl = null, $parent_field = null): static {
        return $this->_joinParent($alias, $ontbl, $parent_field);
    }

    /**
     * Adds LEFT JOIN on same table by parent-field
     *
     * @param string $alias
     * @param null   $ontbl
     * @param null   $parent_field Default - "parent_id"
     *
     * @return $this
     */
    public function leftParent(string $alias, $ontbl = null, $parent_field = null): static {
        return $this->_joinParent($alias, $ontbl, $parent_field, TableClauseItem::J_LEFT);
    }

    public function a($alias): static {
        $mainItem = $this->mainItem();
        $mainItem->table()->as($alias);
        array_shift($this->items);
        $this->items = [$alias => $mainItem] + $this->items;

        return $this;
    }

    public function aliases(): array {
        return array_keys($this->items);
    }

    public function useIndex($index): static {
        $this->lastItem()->setUseIndex($index);

        return $this;
    }

    public function addItem(TableClauseItem $item): static {
        $alias = $item->table()->alias();
        if (!$alias) {
            throw new \InvalidArgumentException('Alias is not defined for table');
        }
        if (!empty($this->items[$alias])) {
            throw new \InvalidArgumentException("Table with alias '$alias' is already in list");
        }

        $this->items[$alias] = $item;
        if (!$this->mainItem) $this->setMainItem($item);

        return $this->setLastItem($item);
    }

    public function createItem($table, $alias = null): TableClauseItem {
        return new TableClauseItem($table, $alias);
    }

    /**
     * @return Condition
     */
    protected function createCondition(): Condition {
        return \VV\Db\Sql::condition();
    }

    /**
     * @param TableClauseItem $firstItem
     *
     * @return $this
     */
    private function setMainItem(TableClauseItem $firstItem): static {
        $this->mainItem = $firstItem;

        return $this;
    }

    /**
     * @param TableClauseItem $lastItem
     *
     * @return $this
     */
    private function setLastItem(TableClauseItem $lastItem): static {
        $this->lastItem = $lastItem;

        return $this;
    }

    private function _join($tbl, $on, $alias, $joinType = null): static {
        if (!$this->items) throw new \LogicException('Main table does not set');

        return $this->_add($tbl, $on, $alias, $joinType);
    }

    private function _joinBack($tbl, $backTableAlias = null, $alias = null, $joinType = null): static {
        $backItem = $this->itemOrLast($backTableAlias);
        $backTblMdl = $backItem->tableModel();
        if (!$backTblMdl) throw new \LogicException('Can\'t determine pk field of relation table');

        if (!$backTableAlias) $backTableAlias = $backItem->table()->alias();

        return $this->_join($tbl, "$backTableAlias.{$backTblMdl->pk()}", $alias, $joinType);
    }

    private function _joinParent($alias, $parentTableAlias, $parentField, $joinType = null): static {
        $parentItem = $this->itemOrLast($parentTableAlias);
        $parentTblMdl = $parentItem->tableModel();
        if (!$parentTblMdl) throw new \LogicException('Can\'t determine pk field of relation table');

        if (!$parentTableAlias) $parentTableAlias = $parentItem->table()->alias();
        if (!$parentField) $parentField = 'parent_id';

        $on = new \VV\Db\Sql\Condition\Predicates\Compare(
            SqlObj::create("$alias.{$parentTblMdl->pk()}"),
            SqlObj::create("$parentTableAlias.$parentField")
        );

        return $this->_join($parentTblMdl, $on, $alias, $joinType);
    }

    private function _add($tbl, $on = null, $alias = null, $joinType = null): static {
        $item = $this->createItem($tbl, $alias);
        if ($this->items) {
            if (!$on instanceof Condition) {
                $cond = $this->createCondition();

                if ($on instanceof Condition\Predicate) {
                    $cond->addPredicItem($on);
                } elseif (is_array($on)) {
                    $cond->expr($on[0])->custom(...array_slice($on, 1));
                } else {
                    $lastItem = $this->lastItem();

                    if (!$on) $on = $lastItem->table()->alias();

                    /** @var SqlObj $rfield */
                    $rfield = null;
                    if (is_string($on) && str_starts_with($on, '.')) {
                        $rfield = SqlObj::create(substr($on, 1));
                        if ($rfield) {
                            $prevAlais = $lastItem->table()->alias();
                            $rfield->setOwner($prevAlais);
                        }
                    }
                    if (!$rfield) {
                        $rfield = SqlObj::create($on);
                        if ($rfield) {
                            if (!$rfield->owner()) {
                                $mdl = $item->tableModel();
                                if (!$mdl) {
                                    throw new \LogicException('Can\'t determine previous table PK field');
                                }

                                $rfield = $rfield->createChild($mdl->pk());
                            }
                        }
                    }

                    $lfield = null;
                    if ($rfield) {
                        $lfield = SqlObj::create($rfield->name(), $item->table()->alias());
                    } else {
                        [$lfield, $rfield] = $this->parseCustomCompare($on);
                    }

                    if ($rfield && $lfield) {
                        if ($lfield->path() === $rfield->path()) {
                            throw new \LogicException('leftfield == rightfield');
                        }

                        $cond->expr($lfield)->eq($rfield);
                    } else {
                        $cond->expr($on)->custom();
                    }
                }
                $on = $cond;
            }
            $item->setJoin($on, $joinType);
        }

        return $this->addItem($item);
    }

    /**
     * @param string $cmpstr
     *
     * @return array|null
     */
    private function parseCustomCompare(string $cmpstr): ?array {
        $objs = explode('=', $cmpstr);
        if (count($objs) != 2) return null;

        $leftObj = SqlObj::create($objs[0]);
        $rightObj = SqlObj::create($objs[1]);

        if ($leftObj && $rightObj) return [$leftObj, $rightObj];

        return null;
    }
}
