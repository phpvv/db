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

use VV\Db\Sql\Clauses\DatasetClause;
use VV\Db\Sql\Clauses\InsertedIdClause;
use VV\Db\Sql\Clauses\TableClause;
use VV\Db\Sql\InsertQuery;
use VV\Db\Sql\SelectQuery;

/**
 * Class Insert
 *
 * @package VV\Db\Driver\Sql\Stringifier
 */
class InsertStringifier extends ModificatoryStringifier
{
    private InsertQuery $insertQuery;
    private ?SqlPart $columnsPart = null;
    /** @var SqlPart[] */
    private ?array $valuesPart = null;

    /**
     * InsertStringifier constructor.
     *
     * @param InsertQuery $insertQuery
     * @param Factory     $factory
     */
    public function __construct(InsertQuery $insertQuery, Factory $factory)
    {
        parent::__construct($factory);
        $this->insertQuery = $insertQuery;
    }

    /**
     * @return InsertQuery
     */
    final public function getInsertQuery(): InsertQuery
    {
        return $this->insertQuery;
    }

    /**
     * @return SqlPart
     */
    final public function getColumnsPart(): SqlPart
    {
        if (!$this->columnsPart) {
            if ($this->isStdFieldsValues()) {
                $this->columnsPart = $this->buildFieldsPart();
            } else {
                $this->fillFieldsValuesFromDataset();
            }
        }

        return $this->columnsPart;
    }

    /**
     * @return SqlPart[]
     */
    final public function getValuesPart(): array
    {
        if (!$this->valuesPart) {
            if ($this->isStdFieldsValues()) {
                $this->valuesPart = $this->buildValuesPart();
            } else {
                $this->fillFieldsValuesFromDataset();
            }
        }

        return $this->valuesPart;
    }

    /**
     * @inheritDoc
     */
    public function getSupportedClausesIds(): int
    {
        return InsertQuery::C_DATASET | InsertQuery::C_COLUMNS | InsertQuery::C_VALUES;
    }

    /**
     * @inheritDoc
     */
    public function stringifyRaw(?array &$params): string
    {
        $query = $this->getInsertQuery();
        $table = $query->getTableClause();
        if ($table->isEmpty()) {
            throw new \LogicException('Table is not selected');
        }

        if (count($this->getValuesPart()) > 1) {
            return $this->stringifyMultiValuesInsert($params);
        }

        return $this->stringifyStdInsert($params);
    }

    /**
     * @inheritDoc
     */
    public function getQueryTableClause(): TableClause
    {
        return $this->getInsertQuery()->getTableClause();
    }

    /**
     * @param array|null $params
     *
     * @return string
     */
    protected function stringifyMultiValuesInsert(?array &$params): string
    {
        $sql = $this->stringifyStdInsertIntoClause($params);

        $valuesStr = [];
        foreach ($this->getValuesPart() as $row) {
            $valuesStr[] = $row->embed($params);
        }

        return $sql . ' VALUES ' . implode(', ', $valuesStr);
    }

    /**
     * @param array|null $params
     *
     * @return string
     */
    protected function stringifyStdInsert(?array &$params): string
    {
        $query = $this->getInsertQuery();
        $this->applyInsertedIdClause($query->getInsertedIdClause(), $params);

        return $this->stringifyStdInsertIntoClause($params)
               . $this->stringifyStdValuesClause($params)
               . $this->stringifyOnDupKeyClause($query->getOnDuplicateKeyClause(), $params)
               . $this->stringifyReturnIntoClause($query->getReturnIntoClause(), $params)
               . $this->stringifyReturningClause($query->getReturningClause(), $params);
    }

    /**
     * @return SqlPart
     */
    protected function buildFieldsPart(): SqlPart
    {
        $fields = $this->getInsertQuery()->getColumnsClause()->getItems();

        return $this->fieldListToPart($fields);
    }

    /**
     * @return array[]
     */
    protected function buildValuesPart(): array
    {
        $query = $this->getInsertQuery();
        $fieldsClause = $query->getColumnsClause();
        if ($fieldsClause->isEmpty()) {
            $mainTable = $query->getTableClause()->getMainTableModel();
            $fields = $mainTable ? $mainTable->getColumns()->getNames() : [];
        } else {
            $fields = $fieldsClause->getItems();
        }

        $values = $query->getValuesClause()->getItems();

        return $this->valueListToPart($values, $fields);
    }

    /**
     * @return array
     */
    protected function buildFieldsValuesFromDataset(): array
    {
        [$fields, $values] = $this->getInsertQuery()->getDatasetClause()->split();

        return [
            $this->fieldListToPart($fields),
            $this->valueListToPart([$values], $fields),
        ];
    }

    /**
     * @param $fields
     *
     * @return SqlPart
     */
    protected function fieldListToPart($fields): SqlPart
    {
        $sql = '';
        $params = [];
        if ($fields) {
            $sql = " (" . $this->stringifyColumnList($fields, $params) . ")";
        }

        return $this->createSqlPart($sql, $params);
    }

    /**
     * @param array[] $values
     * @param array   $fields
     *
     * @return array
     */
    protected function valueListToPart(array $values, array $fields): array
    {
        foreach ($values as &$row) {
            if (!$row) {
                $row = null;
                continue;
            }

            $params = [];
            if ($row instanceof SelectQuery) {
                $row = $this->getFactory()
                    ->createSelectStringifier($row)
                    ->stringifyRaw($params);
            } else {
                foreach ($row as $i => &$val) {
                    $field = $fields[$i] ?? null;
                    $val = $this->stringifyValueToSave($val, $field, $params);
                }
                unset($val);
                $row = '(' . implode(', ', $row) . ')';
            }

            $row = $this->createSqlPart($row, $params);
        }
        unset($row);

        return $values;
    }

    /**
     * @return bool
     */
    protected function isSelectValues(): bool
    {
        $valuesClause = $this->getInsertQuery()->getValuesClause();
        if (!$valuesClause->getItems()) {
            return false;
        }

        return $valuesClause->getItems()[0] instanceof SelectQuery;
    }

    /**
     * @return bool
     */
    protected function isStdFieldsValues(): bool
    {
        return !$this->getInsertQuery()->getValuesClause()->isEmpty();
    }

    /**
     * @param $params
     *
     * @return string
     */
    protected function stringifyStdValuesClause(&$params): string
    {
        $values = $this->getValuesPart()[0];
        if (!$values) {
            return ' DEFAULT VALUES';
        }

        $str = ' ' . $values->embed($params);
        if ($this->isSelectValues()) {
            return $str;
        }

        return ' VALUES' . $str;
    }

    /**
     * @param DatasetClause $dataset
     * @param array|null    $params
     *
     * @return string
     */
    protected function stringifyOnDupKeyClause(DatasetClause $dataset, ?array &$params): string
    {
        if ($dataset->isEmpty()) {
            return '';
        }
        throw new \LogicException('onDupKey is not supported by this stringifier');
    }

    /**
     * @param InsertedIdClause $insertedIdClause
     * @param array|null &     $params
     */
    protected function applyInsertedIdClause(InsertedIdClause $insertedIdClause, ?array &$params)
    {
        if ($insertedIdClause->isEmpty()) {
            return;
        }
        throw new \LogicException('InsertedIdClause is not supported by this stringifier');
    }

    /**
     * @param array|null $params
     *
     * @return string
     */
    protected function stringifyStdInsertIntoClause(?array &$params): string
    {
        $fields = $this->getColumnsPart();
        $table = $this->buildTableSql($this->getInsertQuery()->getTableClause());

        /** @noinspection SqlNoDataSourceInspection */
        return "INSERT INTO {$table->embed($params)} {$fields->embed($params)}";
    }

    /**
     * @inheritDoc
     */
    protected function useAliasForTable(TableClause $table): bool
    {
        return false;
    }

    private function fillFieldsValuesFromDataset(): void
    {
        [$this->columnsPart, $this->valuesPart] = $this->buildFieldsValuesFromDataset();
    }
}
