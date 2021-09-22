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

namespace VV\Db\Sql\Stringifiers;

use VV\Db\Model\Column;
use VV\Db\Param;
use VV\Db\Sql;
use VV\Db\Sql\Clauses\ColumnsClause;
use VV\Db\Sql\Clauses\DatasetClause;
use VV\Db\Sql\Clauses\ReturnIntoClause;
use VV\Db\Sql\Clauses\ReturnIntoClauseItem;
use VV\Db\Sql\Expressions\Expression;

/**
 * Class ModificatoryStringifier
 *
 * @package VV\Db\Driver\Sql\Stringifier
 */
abstract class ModificatoryStringifier extends QueryStringifier
{
    /** @var ReturnIntoClauseItem[] */
    private array $extraReturnIntoItems = [];
    /** @var Expression[] */
    private array $extraReturningItems = [];

    /**
     * @return ReturnIntoClauseItem[]
     */
    protected function getExtraReturnIntoItems(): array
    {
        return $this->extraReturnIntoItems;
    }

    /**
     * @param string|Expression $expression
     * @param Param             $param
     */
    protected function addExtraReturnInto(string|Expression $expression, Param $param)
    {
        $this->extraReturnIntoItems[] = new ReturnIntoClauseItem($expression, $param);
    }

    /**
     * @return Expression[]
     */
    public function getExtraReturningItems(): array
    {
        return $this->extraReturningItems;
    }

    /**
     * @param string|Expression $expression
     */
    protected function addExtraReturning(string|Expression $expression)
    {
        $this->extraReturningItems[] = Sql::expression($expression);
    }

    /**
     * @param ReturnIntoClause $returnInto
     * @param array|null       $params
     *
     * @return string
     */
    protected function stringifyReturnIntoClause(ReturnIntoClause $returnInto, ?array &$params): string
    {
        $items = $returnInto->getItems();
        if ($extraReturnIntoItems = $this->getExtraReturnIntoItems()) {
            array_push($items, ...$extraReturnIntoItems);
        }

        if (!$items) {
            return '';
        }

        $vars = $expressions = [];
        foreach ($items as $item) {
            $expressions[] = $this->stringifyExpression($item->getExpression(), $params);
            $expressionStringifier = $this->getExpressionStringifier();
            $vars[] = $expressionStringifier->stringifyParam($item->getParam(), $params);
        }

        return ' RETURNING ' . implode(', ', $expressions) . ' INTO ' . implode(', ', $vars);
    }

    /**
     * @param ColumnsClause $returning
     * @param array|null    $params
     *
     * @return string
     */
    protected function stringifyReturningClause(ColumnsClause $returning, ?array &$params): string
    {
        $items = $returning->getItems();
        if ($extraReturningItems = $this->getExtraReturningItems()) {
            array_push($items, ...$extraReturningItems);
        }

        if (!$items) {
            return '';
        }

        return ' RETURNING ' . $this->stringifyColumnList($items, $params, true);
    }

    /**
     * @param mixed      $value
     * @param mixed      $field
     * @param array|null $params
     *
     * @return string
     */
    protected function stringifyValueToSave(mixed $value, mixed $field, ?array &$params): string
    {
        if ($value instanceof Expression) {
            $tmpParams = [];
            $str = $this->stringifyColumn($value, $tmpParams);
            if ($tmpParams) {
                foreach ($tmpParams as $tmp) {
                    $this->stringifyValueToSave($tmp, $field, $params);
                }
            }

            return $str;
        }

        $fieldModel = $this->getColumnModel($field);

        if (!$value instanceof Param) {
            if (Param::isFileValue($value)) {
                // auto-detect file to blob
                $value = Param::blob($value);
            } elseif ($fieldModel && $value !== null) {
                // auto-detect large strings to b/clob
                if ($fieldModel->getType() == Column::T_TEXT) {
                    $value = Param::text($value);
                } elseif ($fieldModel->getType() == Column::T_BLOB) {
                    $value = Param::blob($value);
                }
            }
        }

        if ($value instanceof Param) {
            /** @var Param $value */
            $value->setValue($this->prepareParamValueToSave($value->getValue(), $fieldModel));
        } else {
            $value = $this->prepareParamValueToSave($value, $fieldModel);
        }

        // get custom expression
        if ($expr = $this->expressionValueToSave($value, $field)) {
            return $expr->embed($params);
        }

        return $this->stringifyParam($value, $params);
    }

    protected function prepareParamValueToSave(mixed $value, ?Column $field): mixed
    {
        if ($value instanceof \DateTimeInterface) {
            if (!$field) {
                throw new \InvalidArgumentException('Can\'t detect format for \DateTimeInterface');
            }

            return $this->formatDateTimeForField($value, $field);
        }

        if ($value instanceof \Stringable) {
            $value = (string)$value;
        }

        if ($value === '') {
            return null;
        }

        return $value;
    }

    /**
     * @param DatasetClause $dataset
     * @param array|null    $params
     *
     * @return string
     */
    protected function stringifyDataset(DatasetClause $dataset, ?array &$params): string
    {
        $set = [];
        $exprStringifier = $this->getExpressionStringifier();
        foreach ($dataset->getItems() as $item) {
            $fieldStr = $exprStringifier->stringifyDbObject($field = $item->getColumn(), $params);
            $valueStr = $this->stringifyValueToSave($item->getValue(), $field, $params);
            $set[] = "$fieldStr=$valueStr";
        }

        return implode(', ', $set);
    }

    /**
     * @param mixed $value
     * @param mixed $column
     *
     * @return SqlPart|null
     */
    protected function expressionValueToSave(mixed $value, mixed $column): ?SqlPart
    {
        return null;
    }
}
