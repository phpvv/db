<?php declare(strict_types=1);

/*
 * This file is part of the VV package.
 *
 * (c) Volodymyr Sarnytskyi <v00v4n@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
namespace VV\Db\Sql\Stringifiers;

use VV\Db\Model\Field;
use VV\Db\Param;
use VV\Db\Sql;
use VV\Db\Sql\Clauses\ReturnIntoClauseItem;


/**
 * Class ModificatoryStringifier
 *
 * @package VV\Db\Driver\Sql\Stringifier
 */
abstract class ModificatoryStringifier extends QueryStringifier
{

    private $advReturnInto = [];

    /**
     * @return ReturnIntoClauseItem[]
     */
    protected function advReturnInto()
    {
        return $this->advReturnInto;
    }

    protected function addAdvReturnInto($field, $value)
    {
        $this->advReturnInto[] = new ReturnIntoClauseItem($field, $value);
    }

    /**
     * @param Sql\Clauses\ReturnIntoClause $returnInto
     * @param                              $params
     *
     * @return string
     */
    protected function strReturnIntoClause(Sql\Clauses\ReturnIntoClause $returnInto, &$params)
    {
        $items = $returnInto->items();
        if ($advReturnInto = $this->advReturnInto()) {
            array_push($items, ...$advReturnInto);
        }

        if (!$items) {
            return '';
        }

        $vars = $exprs = [];
        foreach ($items as $item) {
            $exprs[] = $this->strExpr($item->expression(), $params);
            /** @var \VV\Db\Sql\Stringifiers\ExpressoinStringifier $exprStringifier */
            $exprStringifier = $this->exprStringifier();
            $vars[] = $exprStringifier->strParam($item->param(), $params);
        }

        return ' RETURNING ' . implode(', ', $exprs) . ' INTO ' . implode(', ', $vars);
    }

    protected function strValueToSave($value, $field, &$params)
    {
        if ($value instanceof Sql\Expressions\Expression) {
            $tmparams = [];
            $str = $this->strColumn($value, $tmparams);
            if ($tmparams) {
                foreach ($tmparams as $tmp) {
                    $this->strValueToSave($tmp, $field, $params);
                }
            }

            return $str;
        }

        $fieldModel = $this->fieldModel($field, $this->queryTableClause());

        if (!$value instanceof Param) {
            if (Param::isFileValue($value)) {
                // auto detect file to blob
                $value = Param::blob($value);
            } elseif ($fieldModel && $value !== null) {
                // auto detect large strings to b/clob
                if ($fieldModel->getType() == Field::T_TEXT) {
                    $value = Param::text($value);
                } elseif ($fieldModel->getType() == Field::T_BLOB) {
                    $value = Param::blob($value);
                }
            }
        }

        if ($fieldModel) {
            // prepare value according db table model
            if ($value instanceof Param) {
                /** @var Param $value */
                $value->setValue($fieldModel->prepareValueToSave($value->getValue()));
            } else {
                $value = $fieldModel->prepareValueToSave($value);
            }
        }

        // get custom expression
        if ($expr = $this->exprValueToSave($value, $field)) {
            return $expr->embed($params);
        }

        return $this->strParam($value, $params);
    }

    protected function strDataset(Sql\Clauses\DatasetClause $dataset, &$params)
    {
        $set = [];
        $exprStringifier = $this->exprStringifier();
        foreach ($dataset->items() as $item) {
            $fldstr = $exprStringifier->strSqlObj($field = $item->field(), $params);
            $valstr = $this->strValueToSave($item->value(), $field, $params);
            $set[] = "$fldstr=$valstr";
        }

        return implode(', ', $set);
    }

    /**
     * @param mixed $value
     * @param mixed $field
     *
     * @return \VV\Db\Sql\Stringifiers\PlainSql|null
     */
    protected function exprValueToSave($value, $field)
    {
        return null;
    }
}
