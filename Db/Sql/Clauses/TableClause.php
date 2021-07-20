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
use VV\Db\Sql;
use VV\Db\Sql\Clauses\TableClauseItem as Item;
use VV\Db\Sql\Condition;
use VV\Db\Sql\Expressions\DbObject;
use VV\Db\Sql\Expressions\Expression;
use VV\Db\Sql\Predicates\ComparePredicate;
use VV\Db\Sql\Predicates\Predicate;

/**
 * Class Table
 *
 * @package VV\Db\Sql\Clauses
 * @method Item[] getItems(): array
 */
class TableClause extends ItemList
{
    private ?Item $mainItem = null;
    private ?Item $lastItem = null;

    /**
     * @param string $alias
     *
     * @return Item
     */
    public function getItem(string $alias): Item
    {
        if (!$this->hasItem($alias)) {
            throw new \OutOfBoundsException("Item with alias '$alias' not found");
        }

        return $this->getItems()[$alias];
    }

    /**
     * @return Item
     */
    public function getMainItem(): Item
    {
        if (!$this->mainItem) {
            throw new \LogicException('Main Item is not defined yet');
        }

        return $this->mainItem;
    }

    /**
     * @return Item
     */
    public function getLastItem(): Item
    {
        if (!$this->lastItem) {
            throw new \LogicException('Last Item is not defined yet');
        }

        return $this->lastItem;
    }

    /**
     * @param string $alias
     *
     * @return bool
     */
    public function hasItem(string $alias): bool
    {
        return isset($this->items[$alias]);
    }

    /**
     * @param string|null $alias
     *
     * @return Item
     */
    public function getItemOrMain(?string $alias): Item
    {
        return $alias ? $this->getItem($alias) : $this->getMainItem();
    }

    /**
     * @param string|null $alias
     *
     * @return Item
     */
    public function getItemOrLast(?string $alias): Item
    {
        return $alias ? $this->getItem($alias) : $this->getLastItem();
    }

    /**
     * Returns table with alias == $alias
     *
     * @param string $alias
     *
     * @return Table|null
     */
    public function getTableModel(string $alias): ?Table
    {
        return $this->getItem($alias)->getTableModel();
    }

    /**
     * @return Table|null
     */
    public function getMainTableModel(): ?Table
    {
        return $this->getMainItem()->getTableModel();
    }

    /**
     * @return Table|null
     */
    public function getLastTableModel(): ?Table
    {
        return $this->getLastItem()->getTableModel();
    }

    /**
     * Returns table with alias == $alias
     *
     * @param string|null $alias
     *
     * @return Table|null
     */
    public function getTableModelOrMain(?string $alias): ?Table
    {
        return $alias
            ? $this->getTableModel($alias)
            : $this->getMainTableModel();
    }

    /**
     * Returns table with alias == $alias
     *
     * @param string $alias
     *
     * @return Table|null
     */
    public function getTableModelOrLast(string $alias): ?Table
    {
        return $alias
            ? $this->getTableModel($alias)
            : $this->getLastTableModel();
    }

    /**
     * @return string|null
     */
    public function getMainTableAlias(): ?string
    {
        return $this->getMainItem()->getTable()->getAlias();
    }

    /**
     * @return string|null
     */
    public function getLastTableAlias(): ?string
    {
        return $this->getLastItem()->getTable()->getAlias();
    }

    /**
     * Returns PK of table with alias == $alias
     *
     * @param string $alias
     *
     * @return string|null
     */
    public function getTablePk(string $alias): ?string
    {
        return $this->getTableModel($alias)?->getPk();
    }

    /**
     * Returns PK of main table with alias == $alias
     *
     * @return string|null
     */
    public function getMainTablePk(): ?string
    {
        return $this->getMainTableModel()?->getPk();
    }

    /**
     * Returns PK of last table with alias == $alias
     *
     * @return string|null
     */
    public function getLastTablePk(): ?string
    {
        return $this->getLastTableModel()?->getPk();
    }

    /**
     * Returns name of Primary Key field of table with alias == $alias
     *
     * @param string $alias
     *
     * @return string|null
     */
    public function getPkByAlias(string $alias): ?string
    {
        return $this->getTableModel($alias)?->getPk();
    }

    public function setMainTable(string|Table|Expression $table, string $alias = null): static
    {
        $this->items = [];
        $this->mainItem = null;
        $this->createAndAddItem($table, null, $alias);

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
    public function join(
        string|Table|Expression $table,
        string|array|Condition $on = null,
        string $alias = null
    ): static {
        return $this->addJoin($table, $on, $alias);
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
    public function left(
        string|Table|Expression $table,
        string|array|Condition $on = null,
        string $alias = null
    ): static {
        return $this->addJoin($table, $on, $alias, Item::J_LEFT);
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
    public function right(
        string|Table|Expression $table,
        string|array|Condition $on = null,
        string $alias = null
    ): static {
        return $this->addJoin($table, $on, $alias, Item::J_RIGHT);
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
    public function full(
        string|Table|Expression $table,
        string|array|Condition $on = null,
        string $alias = null
    ): static {
        return $this->addJoin($table, $on, $alias, Item::J_FULL);
    }

    //endregion
    //region join - back

    /**
     * Adds (INNER) JOIN clause backward
     *
     * @param string|Table|Expression $table
     * @param string|null             $onTable
     * @param string|null             $alias
     *
     * @return $this
     */
    public function joinBack(string|Table|Expression $table, string $onTable = null, string $alias = null): static
    {
        return $this->addJoinBack($table, $onTable, $alias);
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
    public function leftBack(string|Table|Expression $table, string $onTable = null, string $alias = null): static
    {
        return $this->addJoinBack($table, $onTable, $alias, Item::J_LEFT);
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
    public function joinParent(string $alias, string $onTable = null, string $parentField = null): static
    {
        return $this->addJoinParent($alias, $onTable, $parentField);
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
    public function leftParent(string $alias, string $onTable = null, string $parentField = null): static
    {
        return $this->addJoinParent($alias, $onTable, $parentField, Item::J_LEFT);
    }

    /**
     * @param string $alias
     *
     * @return $this
     */
    public function setMainTableAlias(string $alias): static
    {
        $mainItem = $this->getMainItem();
        $mainItem->getTable()->as($alias);
        array_shift($this->items);
        $this->items = [$alias => $mainItem] + $this->items;

        return $this;
    }

    /**
     * @return string[]
     */
    public function getAliases(): array
    {
        return array_keys($this->items);
    }

    public function useIndex($index): static
    {
        $this->getLastItem()->setUseIndex($index);

        return $this;
    }

    public function addItem(Item $item): static
    {
        $alias = $item->getTable()->getAlias();
        if (!$alias) {
            throw new \InvalidArgumentException('Alias is not defined for table');
        }
        if (!empty($this->items[$alias])) {
            throw new \InvalidArgumentException("Table with alias '$alias' is already in list");
        }

        $this->items[$alias] = $item;
        if (!$this->mainItem) {
            $this->setMainItem($item);
        }

        return $this->setLastItem($item);
    }

    /**
     * @param Item $firstItem
     *
     * @return $this
     */
    protected function setMainItem(Item $firstItem): static
    {
        $this->mainItem = $firstItem;

        return $this;
    }

    /**
     * @param Item $lastItem
     *
     * @return $this
     */
    protected function setLastItem(Item $lastItem): static
    {
        $this->lastItem = $lastItem;

        return $this;
    }

    protected function addJoin($tbl, $on, $alias, $joinType = null): static
    {
        if (!$this->items) {
            throw new \LogicException('Main table does not set yet');
        }

        return $this->createAndAddItem($tbl, $on, $alias, $joinType);
    }

    protected function addJoinBack($table, $backTableAlias = null, $alias = null, $joinType = null): static
    {
        $backItem = $this->getItemOrLast($backTableAlias);
        $backTableModel = $backItem->getTableModel();
        if (!$backTableModel) {
            throw new \LogicException('Can\'t determine pk field of relation table');
        }

        if (!$backTableAlias) {
            $backTableAlias = $backItem->getTable()->getAlias();
        }

        return $this->addJoin($table, "$backTableAlias.{$backTableModel->getPk()}", $alias, $joinType);
    }

    protected function addJoinParent($alias, $parentTableAlias, $parentField, $joinType = null): static
    {
        $parentItem = $this->getItemOrLast($parentTableAlias);
        $parentTblMdl = $parentItem->getTableModel();
        if (!$parentTblMdl) {
            throw new \LogicException('Can\'t determine pk field of relation table');
        }

        if (!$parentTableAlias) {
            $parentTableAlias = $parentItem->getTable()->getAlias();
        }
        if (!$parentField) {
            $parentField = 'parent_id';
        }

        $on = new ComparePredicate(
            DbObject::create("$alias.{$parentTblMdl->getPk()}"),
            DbObject::create("$parentTableAlias.$parentField")
        );

        return $this->addJoin($parentTblMdl, $on, $alias, $joinType);
    }

    /**
     * @param string $comparationString
     *
     * @return array|null
     */
    protected function parseCustomCompare(string $comparationString): ?array
    {
        $objs = explode('=', $comparationString);
        if (count($objs) != 2) {
            return null;
        }

        $leftObj = DbObject::create($objs[0]);
        $rightObj = DbObject::create($objs[1]);

        if ($leftObj && $rightObj) {
            return [$leftObj, $rightObj];
        }

        return null;
    }

    /**
     * @param string|array|Condition|Predicate $on
     * @param Item                             $item
     *
     * @return Condition
     */
    protected function prepareOnCondition(mixed $on, Item $item): Condition
    {
        if ($on instanceof Condition) {
            return $on;
        }

        $condition = $this->createCondition();
        if ($on instanceof Predicate) {
            return $condition->addPredicate($on);
        }
        // custom on as array: ['f.foo = b.bar AND b.field = ?', ['fieldValue']]
        if (is_array($on)) {
            return $condition->expr($on[0])->custom(...array_slice($on, 1));
        }

        // todo: need comment with examples
        $lastItem = $this->getLastItem();
        if (!$on) {
            $on = $lastItem->getTable()->getAlias();
        }

        /** @var DbObject $rightField */
        $rightField = null;
        if (is_string($on) && str_starts_with($on, '.')) {
            $rightField = DbObject::create(substr($on, 1));
            if ($rightField) {
                $prevAliAs = $lastItem->getTable()->getAlias();
                $rightField->setOwner($prevAliAs);
            }
        }

        if (!$rightField) {
            $rightField = DbObject::create($on);
            if ($rightField) {
                if (!$rightField->getOwner()) {
                    $tableModel = $item->getTableModel();
                    if (!$tableModel) {
                        throw new \LogicException('Can\'t determine previous table PK field');
                    }

                    $rightField = $rightField->createChild($tableModel->getPk());
                }
            }
        }

        if ($rightField) {
            $leftField = DbObject::create($rightField->getName(), $item->getTable()->getAlias());
        } else {
            [$leftField, $rightField] = $this->parseCustomCompare($on);
        }

        if ($rightField && $leftField) {
            if ($leftField->getPath() === $rightField->getPath()) {
                throw new \LogicException('JOIN ON leftField == rightField');
            }

            return $condition->expr($leftField)->eq($rightField);
        }

        return $condition->expr($on)->custom();
    }

    protected function createAndAddItem($table, $on = null, $alias = null, $joinType = null): static
    {
        $item = $this->createItem($table, $alias);
        if ($this->items) {
            $on = $this->prepareOnCondition($on, $item);
            $item->setJoin($on, $joinType);
        }

        return $this->addItem($item);
    }

    /**
     * @param string|Table|Expression $table
     * @param string|null             $alias
     *
     * @return Item
     */
    public function createItem(string|Table|Expression $table, string $alias = null): Item
    {
        return new Item($table, $alias);
    }

    /**
     * @return Condition
     */
    protected function createCondition(): Condition
    {
        return Sql::condition();
    }
}
