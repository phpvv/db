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

use VV\Db\Model\DataObject;
use VV\Db\Model\Table;
use VV\Db\Sql;
use VV\Db\Sql\Condition;
use VV\Db\Sql\Expressions\DbObject;
use VV\Db\Sql\Expressions\Expression;

/**
 * Class Item
 *
 * @package VV\Db\Sql\Clause\Table
 */
class TableClauseItem
{
    public const J_INNER = 'JOIN',
        J_LEFT = 'LEFT JOIN',
        J_RIGHT = 'RIGHT JOIN',
        J_FULL = 'FULL JOIN',
        J_OUTER = 'OUTER JOIN';

    private Expression $table;
    private ?Table $tableModel = null;
    private ?Condition $joinCondition = null;
    private ?string $joinType = null;
    private ?array $useIndex = null;

    /**
     * Item constructor.
     *
     * @param string|Table|Expression $table
     * @param string|null             $alias
     * @param Condition|null          $joinOn
     * @param string|null             $joinType
     */
    public function __construct(
        string|Table|Expression $table,
        string $alias = null,
        Condition $joinOn = null,
        string $joinType = null
    ) {
        $this->setTable($table, $alias);
        if ($joinOn) {
            $this->setJoin($joinOn, $joinType);
        }
    }


    /**
     * @return Expression
     */
    public function getTable(): Expression
    {
        return $this->table;
    }

    /**
     * @return Table|null
     */
    public function getTableModel(): ?Table
    {
        return $this->tableModel;
    }

    /**
     * @return Condition|null
     */
    public function getJoinCondition(): ?Condition
    {
        return $this->joinCondition;
    }

    /**
     * @return string|null
     */
    public function getJoinType(): ?string
    {
        return $this->joinType;
    }

    /**
     * @return array|null
     */
    public function getUseIndex(): ?array
    {
        return $this->useIndex;
    }

    /**
     * @param array|string|null $useIndex
     *
     * @return $this
     */
    public function setUseIndex(array|string|null $useIndex): static
    {
        $this->useIndex = (array)$useIndex;

        return $this;
    }

    /**
     * @param Condition   $on
     * @param string|null $type
     *
     * @return $this
     */
    public function setJoin(Condition $on, string $type = null): static
    {
        return $this->setJoinCondition($on)->setJoinType($type ?: self::J_INNER);
    }

    /**
     * @param string|Expression|Table $table
     * @param string|null             $alias
     *
     * @return $this
     */
    protected function setTable(string|Expression|Table $table, string $alias = null): static
    {
        if ($table instanceof Table) {
            return $this
                ->setTableModel($table)
                ->setTable($table->getName(), $alias ?: $table->getDefaultAlias());
        }

        $tbl = Sql::expression($table);
        if ($alias) {
            $tbl->as($alias);
        } elseif (!$tbl->alias()) {
            if (!$tbl instanceof DbObject) {
                throw new \LogicException('Can\'t determine alias for table');
            }

            $alias = DataObject::nameToAlias($tbl->name());
            $tbl->as($alias);
        }

        $this->table = $tbl;

        return $this;
    }

    /**
     * @param Table $table
     *
     * @return $this
     */
    protected function setTableModel(Table $table): static
    {
        $this->tableModel = $table;

        return $this;
    }

    /**
     * @param Condition $on
     *
     * @return $this
     */
    protected function setJoinCondition(Condition $on): static
    {
        $this->joinCondition = $on;

        return $this;
    }

    /**
     * @param string $type
     *
     * @return $this
     */
    protected function setJoinType(string $type): static
    {
        if (!self::checkJoinType($type)) {
            throw new \InvalidArgumentException('Wrong join type');
        }

        $this->joinType = $type;

        return $this;
    }

    /**
     * @param string $joinType
     *
     * @return bool
     */
    public static function checkJoinType(string $joinType): bool
    {
        return match ($joinType) {
            self::J_INNER, self::J_LEFT, self::J_RIGHT, self::J_FULL, self::J_OUTER => true,
            default => false,
        };
    }
}
