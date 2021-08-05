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

use VV\Db\Sql\Clauses\InsertedIdClause;
use VV\Db\Sql\InsertQuery;

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
        return parent::getSupportedClausesIds() | InsertQuery::C_RETURN_INTO | InsertQuery::C_RETURN_INS_ID;
    }

    /**
     * @inheritDoc
     */
    protected function stringifyMultiValuesInsert(?array &$params): string
    {
        $table = $this->buildTableSql($this->insertQuery()->getTableClause());
        $fields = $this->fieldsPart();

        $sql = 'INSERT ALL ';
        foreach ($this->valuesPart() as $row) {
            $sql .= "INTO {$table->embed($params)} {$fields->embed($params)} VALUES {$row->embed($params)}";
        }
        $sql .= 'SELECT * FROM dual';

        return $sql;
    }

    /**
     * @inheritDoc
     */
    protected function applyInsertedIdClause(InsertedIdClause $insertedIdClause)
    {
        if ($insertedIdClause->isEmpty()) {
            return;
        }

        $query = $this->insertQuery();
        $pk = $insertedIdClause->getPk() ?: $query->getMainTablePk();
        $field = \VV\Db\Sql\Expressions\DbObject::create($pk);

        $pkField = null;
        if ($mtm = $query->getTableClause()->getMainTableModel()) {
            $pkField = $mtm->getFields()->get($pk);
        }
        if (!$param = $insertedIdClause->getParam()) {
            if ($pkField) {
                $isNum = $pkField->getType() == \VV\Db\Model\Field::T_NUM;
            } else {
                $isNum = true;
            }

            $type = $isNum ? \VV\Db\Param::T_INT : \VV\Db\Param::T_CHR;
            $param = new \VV\Db\Param($type);
        }

        if (!$param->getSize() && $param->isSizable()) {
            if (!$pkField) {
                throw new \LogicException('pkField is empty');
            }

            $param->setSize($pkField->getLength());
        }

        $this->addAdvReturnInto($field, $param->setForInsertedId());
    }
}
