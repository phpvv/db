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

use VV\Db\Model\Table as TableModel;
use VV\Db\Sql\Condition as ConditionClause;
use VV\Db\Sql\Expression as SqlExpr;

/**
 * Class Item
 *
 * @package VV\Db\Sql\Clause\Table
 */
class TableClauseItem {

    const J_INNER = 'JOIN',
        J_LEFT = 'LEFT JOIN',
        J_RIGHT = 'RIGHT JOIN',
        J_FULL = 'FULL JOIN',
        J_OUTER = 'OUTER JOIN';

    /**
     * @var SqlExpr
     */
    private $table;

    /**
     * @var TableModel
     */
    private $tableModel;

    /**
     * @var ConditionClause
     */
    private $joinCondition;

    /**
     * @var string
     */
    private $joinType;

    /**
     * @var array
     */
    private $useIndex;

    /**
     * Item constructor.
     *
     * @param string|SqlExpr|TableModel $table
     * @param string                    $alias
     * @param ConditionClause           $joinCond
     * @param string                    $joinType
     *
     */
    public function __construct($table, $alias = null, ConditionClause $joinCond = null, $joinType = null) {
        $this->setTable($table, $alias);
        if ($joinCond) $this->setJoin($joinCond, $joinType);
    }


    /**
     * @return SqlExpr
     */
    public function table() {
        return $this->table;
    }

    /**
     * @return TableModel
     */
    public function tableModel() {
        return $this->tableModel;
    }

    /**
     * @return string
     */
    public function joinType() {
        return $this->joinType;
    }

    /**
     * @return ConditionClause
     */
    public function joinCondition() {
        return $this->joinCondition;
    }

    /**
     * @return array
     */
    public function useIndex() {
        return $this->useIndex;
    }

    /**
     * @param array $useIndex
     *
     * @return $this
     */
    public function setUseIndex(array $useIndex) {
        $this->useIndex = $useIndex;

        return $this;
    }

    /**
     * @param ConditionClause $joinCond
     * @param null            $joinType
     *
     * @return $this
     */
    public function setJoin(ConditionClause $joinCond, $joinType = null) {
        return $this->setJoinCondition($joinCond)->setJoinType($joinType ?: self::J_INNER);
    }

    /**
     * @param string $joinType
     *
     * @return $this
     */
    protected function setJoinType($joinType) {
        if (!self::checkJoinType($joinType)) {
            throw new \InvalidArgumentException('Wrong join type');
        }

        $this->joinType = $joinType;

        return $this;
    }

    /**
     * @param string|SqlExpr|TableModel $table
     * @param string|null               $alias
     *
     * @return $this
     */
    protected function setTable($table, string $alias = null) {
        if ($table instanceof TableModel) {
            return $this
                ->setTableModel($table)
                ->setTable($table->name(), $alias ?: $table->dfltAlias());
        }

        $tbl = \VV\Db\Sql::expression($table);
        if ($alias) {
            $tbl->as($alias);
        } elseif (!$tbl->alias()) {
            if (!$tbl instanceof \VV\Db\Sql\DbObject) {
                throw new \LogicException('Can\'t determine alias for table');
            }

            $alias = \VV\Db\Model\DataObject::name2alias($tbl->name());
            $tbl->as($alias);
        }

        $this->table = $tbl;

        return $this;
    }

    /**
     * @param TableModel $tableModel
     *
     * @return $this
     */
    protected function setTableModel(TableModel $tableModel) {
        $this->tableModel = $tableModel;

        return $this;
    }

    /**
     * @param ConditionClause $joinCondition
     *
     * @return $this
     */
    protected function setJoinCondition(ConditionClause $joinCondition) {
        $this->joinCondition = $joinCondition;

        return $this;
    }

    /**
     * @param string $joinType
     *
     * @return bool
     */
    public static function checkJoinType($joinType) {
        switch ($joinType) {
            case self::J_INNER:
            case self::J_LEFT:
            case self::J_RIGHT:
            case self::J_FULL:
            case self::J_OUTER:
                return true;
            default:
                return false;
        }
    }
}
