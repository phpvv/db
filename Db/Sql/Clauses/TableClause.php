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

use VV\Db\Model\Table;
use VV\Db\Sql\Clauses\TableClauseItem as Item;
use VV\Db\Sql\Condition\Condition;
use VV\Db\Sql\Condition\Predicate;
use VV\Db\Sql\Expressions\DbObject;
use VV\Db\Sql\Expressions\Expression;

/**
 * Class Table
 *
 * @package VV\Db\Sql\Clauses
 * @method Item[] items():array
 */
class TableClause extends ItemList {

    private ?Item $mainItem = null;
    private ?Item $lastItem = null;

    /**
     * @param string $alias
     *
     * @return Item
     */
    public function item(string $alias): Item {
        if (!$this->hasItem($alias)) throw new \OutOfBoundsException("Item with alias '$alias' not found");

        return $this->items()[$alias];
    }

    /**
     * @return Item
     */
    public function mainItem(): Item {
        if (!$this->mainItem) throw new \LogicException('Main Item is not defined yet');

        return $this->mainItem;
    }

    /**
     * @return Item
     */
    public function lastItem(): Item {
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
     * @return Item
     */
    public function itemOrMain(string $alias): Item {
        return $alias ? $this->item($alias) : $this->mainItem();
    }

    /**
     * @param string $alias
     *
     * @return Item
     */
    public function itemOrLast(string $alias): Item {
        return $alias ? $this->item($alias) : $this->lastItem();
    }

    /**
     * Returns table with alias == $alias
     *
     * @param string $alias
     *
     * @return Table|null
     */
    public function tableModel(string $alias): ?Table {
        return $this->item($alias)->tableModel();
    }

    /**
     * @return Table|null
     */
    public function mainTableModel(): ?Table {
        return $this->mainItem()->tableModel();
    }

    /**
     * @return Table|null
     */
    public function lastTableModel(): ?Table {
        return $this->lastItem()->tableModel();
    }

    /**
     * Returns table with alias == $alias
     *
     * @param string|null $alias
     *
     * @return Table|null
     */
    public function tableModelOrMain(?string $alias): ?Table {
        return $alias
            ? $this->tableModel($alias)
            : $this->mainTableModel();
    }

    /**
     * Returns table with alias == $alias
     *
     * @param string $alias
     *
     * @return Table|null
     */
    public function tableModelOrLast(string $alias): ?Table {
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
        return ($table = $this->tableModel($alias)) ? $table->pk() : null;
    }

    /**
     * Returns PK of main table with alias == $alias
     *
     * @return string|null
     */
    public function mainTablePk(): ?string {
        return ($table = $this->mainTableModel()) ? $table->pk() : null;
    }

    /**
     * Returns PK of last table with alias == $alias
     *
     * @return string|null
     */
    public function lastTablePk(): ?string {
        return ($lable = $this->lastTableModel()) ? $lable->pk() : null;
    }

    /**
     * Returns name of Primary Key field of table with alias == $alias
     *
     * @param string $alias
     *
     * @return string|null
     */
    public function pkByAlias(string $alias): ?string {
        return ($table = $this->tableModel($alias)) ? $table->pk() : null;
    }

    public function setMainTable($table, $alias = null): static {
        $this->items = [];
        $this->mainItem = null;
        $this->add($table, null, $alias);

        return $this;
    }

    /**
     * Adds (INNER) JOIN clause
     *
     * @param string|Table|Expression     $table
     * @param string|array|Condition|null $on
     * @param string|null                 $alias
     *
     * @return $this
     */
    public function join(string|Table|Expression $table, string|array|Condition $on = null, string $alias = null): static {
        return $this->_join($table, $on, $alias);
    }

    /**
     * Adds LEFT JOIN clause
     *
     * @param string|Table|Expression     $table
     * @param array|string|Condition|null $on
     * @param string|null                 $alias
     *
     * @return $this
     */
    public function left(string|Table|Expression $table, string|array|Condition $on = null, string $alias = null): static {
        return $this->_join($table, $on, $alias, Item::J_LEFT);
    }

    /**
     * Adds RIGHT JOIN clause
     *
     * @param string|Table|Expression     $table
     * @param string|array|Condition|null $on
     * @param string|null                 $alias
     *
     * @return $this
     */
    public function right(string|Table|Expression $table, string|array|Condition $on = null, string $alias = null): static {
        return $this->_join($table, $on, $alias, Item::J_RIGHT);
    }

    /**
     * Adds FULL JOIN clause
     *
     * @param string|Table|Expression     $table
     * @param string|array|Condition|null $on
     * @param string|null                 $alias
     *
     * @return $this
     */
    public function full(string|Table|Expression $table, string|array|Condition $on = null, string $alias = null): static {
        return $this->_join($table, $on, $alias, Item::J_FULL);
    }

    //endregion
    //region join - back

    /**
     * Adds (INNER) JOIN clause backward
     *
     * @param string|Table|Expression $table
     * @param null                    $onTable
     * @param string|null             $alias
     *
     * @return $this
     */
    public function joinBack(string|Table|Expression $table, $onTable = null, string $alias = null): static {
        return $this->_joinBack($table, $onTable, $alias);
    }

    /**
     * Adds LEFT JOIN clause backward
     *
     * @param string|Table|Expression $table
     * @param string|null             $onTable
     * @param string|null             $alias
     *
     * @return $this
     */
    public function leftBack(string|Table|Expression $table, string $onTable = null, string $alias = null): static {
        return $this->_joinBack($table, $onTable, $alias, Item::J_LEFT);
    }

    //endregion
    //region join - parent

    /**
     * Adds (INNER) JOIN on same table by parent-field
     *
     * @param string      $alias
     * @param string|null $onTable
     * @param string|null $parentField Default - "parent_id"
     *
     * @return $this
     */
    public function joinParent(string $alias, string $onTable = null, string $parentField = null): static {
        return $this->_joinParent($alias, $onTable, $parentField);
    }

    /**
     * Adds LEFT JOIN on same table by parent-field
     *
     * @param string      $alias
     * @param string|null $onTable
     * @param string|null $parentField Default - "parent_id"
     *
     * @return $this
     */
    public function leftParent(string $alias, string $onTable = null, string $parentField = null): static {
        return $this->_joinParent($alias, $onTable, $parentField, Item::J_LEFT);
    }

    /**
     * @param string $alias
     *
     * @return $this
     */
    public function mainTableAs(string $alias): static {
        $mainItem = $this->mainItem();
        $mainItem->table()->as($alias);
        array_shift($this->items);
        $this->items = [$alias => $mainItem] + $this->items;

        return $this;
    }

    /**
     * @return string[]
     */
    public function aliases(): array {
        return array_keys($this->items);
    }

    public function useIndex($index): static {
        $this->lastItem()->setUseIndex($index);

        return $this;
    }

    public function addItem(Item $item): static {
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

    public function createItem($table, $alias = null): Item {
        return new Item($table, $alias);
    }

    /**
     * @return Condition
     */
    protected function createCondition(): Condition {
        return \VV\Db\Sql::condition();
    }

    /**
     * @param Item $firstItem
     *
     * @return $this
     */
    private function setMainItem(Item $firstItem): static {
        $this->mainItem = $firstItem;

        return $this;
    }

    /**
     * @param Item $lastItem
     *
     * @return $this
     */
    private function setLastItem(Item $lastItem): static {
        $this->lastItem = $lastItem;

        return $this;
    }

    private function _join($tbl, $on, $alias, $joinType = null): static {
        if (!$this->items) throw new \LogicException('Main table does not set yet');

        return $this->add($tbl, $on, $alias, $joinType);
    }

    private function _joinBack($table, $backTableAlias = null, $alias = null, $joinType = null): static {
        $backItem = $this->itemOrLast($backTableAlias);
        $backTableModel = $backItem->tableModel();
        if (!$backTableModel) throw new \LogicException('Can\'t determine pk field of relation table');

        if (!$backTableAlias) $backTableAlias = $backItem->table()->alias();

        return $this->_join($table, "$backTableAlias.{$backTableModel->pk()}", $alias, $joinType);
    }

    private function _joinParent($alias, $parentTableAlias, $parentField, $joinType = null): static {
        $parentItem = $this->itemOrLast($parentTableAlias);
        $parentTblMdl = $parentItem->tableModel();
        if (!$parentTblMdl) throw new \LogicException('Can\'t determine pk field of relation table');

        if (!$parentTableAlias) $parentTableAlias = $parentItem->table()->alias();
        if (!$parentField) $parentField = 'parent_id';

        $on = new \VV\Db\Sql\Condition\Predicates\Compare(
            DbObject::create("$alias.{$parentTblMdl->pk()}"),
            DbObject::create("$parentTableAlias.$parentField")
        );

        return $this->_join($parentTblMdl, $on, $alias, $joinType);
    }

    private function add($table, $on = null, $alias = null, $joinType = null): static {
        $item = $this->createItem($table, $alias);
        if ($this->items) {
            $on = $this->prepareOnCondition($on, $item);
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

        $leftObj = DbObject::create($objs[0]);
        $rightObj = DbObject::create($objs[1]);

        if ($leftObj && $rightObj) return [$leftObj, $rightObj];

        return null;
    }

    /**
     * @param string|array|Condition|Predicate $on
     * @param Item                  $item
     *
     * @return Condition
     */
    private function prepareOnCondition(mixed $on, Item $item): Condition {
        if ($on instanceof Condition) return $on;

        $condition = $this->createCondition();
        if ($on instanceof Predicate) {
            return $condition->addPredicItem($on);
        }
        // constom on as array: ['f.foo = b.bar AND b.field = ?', ['fieldValue']]
        if (is_array($on)) {
            return $condition->expr($on[0])->custom(...array_slice($on, 1));
        }

        // todo: need comment with examples
        $lastItem = $this->lastItem();
        if (!$on) $on = $lastItem->table()->alias();

        /** @var DbObject $rightField */
        $rightField = null;
        if (is_string($on) && str_starts_with($on, '.')) {
            $rightField = DbObject::create(substr($on, 1));
            if ($rightField) {
                $prevAlais = $lastItem->table()->alias();
                $rightField->setOwner($prevAlais);
            }
        }

        if (!$rightField) {
            $rightField = DbObject::create($on);
            if ($rightField) {
                if (!$rightField->owner()) {
                    $tableModel = $item->tableModel();
                    if (!$tableModel) {
                        throw new \LogicException('Can\'t determine previous table PK field');
                    }

                    $rightField = $rightField->createChild($tableModel->pk());
                }
            }
        }

        if ($rightField) {
            $leftField = DbObject::create($rightField->name(), $item->table()->alias());
        } else {
            [$leftField, $rightField] = $this->parseCustomCompare($on);
        }

        if ($rightField && $leftField) {
            if ($leftField->path() === $rightField->path()) {
                throw new \LogicException('JOIN ON leftField == rightField');
            }

            return $condition->expr($leftField)->eq($rightField);
        }

        return $condition->expr($on)->custom();
    }
}
