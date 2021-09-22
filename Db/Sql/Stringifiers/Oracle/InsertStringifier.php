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

namespace VV\Db\Sql\Stringifiers\Oracle;

use VV\Db\Param;
use VV\Db\Sql\Clauses\InsertedIdClause;
use VV\Db\Sql\Expressions\DbObject;
use VV\Db\Sql\InsertQuery;
use VV\Db\Sql\ModificatoryQuery;

/**
 * Class Insert
 *
 * @package VV\Db\Driver\Oracle\SqlStringifier
 */
class InsertStringifier extends \VV\Db\Sql\Stringifiers\InsertStringifier
{
    use ModifyUtils;
    use CommonUtils;

    /**
     * @inheritDoc
     */
    public function getSupportedClausesIds(): int
    {
        return parent::getSupportedClausesIds() | ModificatoryQuery::C_RETURN_INTO | InsertQuery::C_RETURN_INSERTED_ID;
    }

    /**
     * @inheritDoc
     */
    protected function stringifyMultiValuesInsert(?array &$params): string
    {
        $table = $this->buildTableSql($this->getInsertQuery()->getTableClause());
        $columnsPart = $this->getColumnsPart();

        $sql = 'INSERT ALL ';
        foreach ($this->getValuesPart() as $row) {
            $sql .= "INTO {$table->embed($params)} {$columnsPart->embed($params)} VALUES {$row->embed($params)}";
        }
        /** @noinspection SqlResolve */
        /** @noinspection SqlNoDataSourceInspection */
        $sql .= 'SELECT * FROM dual';

        return $sql;
    }

    /**
     * @param array|null &$params
     *
     * @inheritDoc
     */
    protected function applyInsertedIdClause(InsertedIdClause $insertedIdClause, ?array &$params)
    {
        if ($insertedIdClause->isEmpty()) {
            return;
        }

        $query = $this->getInsertQuery();
        $pk = $insertedIdClause->getPk() ?: $query->getMainTablePk();
        $column = DbObject::create($pk);

        $pkColumn = null;
        if ($mtm = $query->getTableClause()->getMainTableModel()) {
            $pkColumn = $mtm->getColumns()->get($pk);
        }
        if (!$param = $insertedIdClause->getParam()) {
            $isNum = $pkColumn?->isNumeric() ?? true;

            $type = $isNum ? Param::T_INT : Param::T_STR;
            $param = new Param($type);
        }

        if (!$param->getSize() && $param->isSizable()) {
            if (!$pkColumn) {
                throw new \LogicException('pkColumn is empty');
            }

            $param->setSize($pkColumn->getLength());
        }

        $this->addExtraReturnInto($column, $param->setForInsertedId());
    }
}
