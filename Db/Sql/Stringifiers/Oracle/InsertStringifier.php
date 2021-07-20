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

/**
 * Class Insert
 *
 * @package VV\Db\Driver\Oracle\SqlStringifier
 */
class InsertStringifier extends \VV\Db\Sql\Stringifiers\InsertStringifier
{

    use ModifyUtils;
    use CommonUtils;

    public function supportedClausesIds()
    {
        return parent::supportedClausesIds()
               | \VV\Db\Sql\InsertQuery::C_RETURN_INTO
               | \VV\Db\Sql\InsertQuery::C_RETURN_INS_ID;
    }

    protected function strMultiValuesInsert(&$params)
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

    protected function applyInsertedIdClause(\VV\Db\Sql\Clauses\InsertedIdClause $retinsId)
    {
        if ($retinsId->isEmpty()) {
            return;
        }

        $query = $this->insertQuery();
        $pk = $retinsId->getPk() ?: $query->getMainTablePk();
        $field = \VV\Db\Sql\Expressions\DbObject::create((string)$pk);

        $pkField = null;
        if ($mtm = $query->getTableClause()->getMainTableModel()) {
            $pkField = $mtm->getFields()->get($pk);
        }
        if (!$param = $retinsId->getParam()) {
            if ($pkField) {
                $isnum = $pkField->getType() == \VV\Db\Model\Field::T_NUM;
            } else {
                $isnum = true;
            }

            $type = $isnum ? \VV\Db\Param::T_INT : \VV\Db\Param::T_CHR;
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
