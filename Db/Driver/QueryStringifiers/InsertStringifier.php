<?php declare(strict_types=1);

/*
 * This file is part of the VV package.
 *
 * (c) Volodymyr Sarnytskyi <v00v4n@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
namespace VV\Db\Driver\QueryStringifiers;

use VV\Db\Driver\Driver;
use VV\Db\Driver\QueryStringifiers\PlainSql as PlainSql;
use VV\Db\Sql;
use VV\Db\Sql\InsertQuery as InsertQuery;

/**
 * Class Insert
 *
 * @package VV\Db\Driver\Sql\Stringifier
 */
class InsertStringifier extends ModificatoryStringifier {

    private InsertQuery $insertQuery;
    private ?PlainSql $fieldsPart = null;
    /** @var PlainSql[] */
    private ?array $valuesPart = null;

    /**
     * Insert constructor.
     *
     * @param InsertQuery $insertQuery
     * @param Driver      $driver
     */
    public function __construct(InsertQuery $insertQuery, Driver $driver) {
        parent::__construct($driver);
        $this->insertQuery = $insertQuery;
    }

    /**
     * @return InsertQuery
     */
    final public function insertQuery() {
        return $this->insertQuery;
    }

    /**
     * @return PlainSql
     */
    final public function fieldsPart() {
        if (!$this->fieldsPart) {
            if ($this->isStdFieldsValues()) {
                $this->fieldsPart = $this->buildFieldsPart();
            } else {
                $this->fillFieldsValuesFromDataset();
            }
        }

        return $this->fieldsPart;
    }

    /**
     * @return PlainSql[]
     */
    final public function valuesPart() {
        if (!$this->valuesPart) {
            if ($this->isStdFieldsValues()) {
                $this->valuesPart = $this->buildValuesPart();
            } else {
                $this->fillFieldsValuesFromDataset();
            }
        }


        return $this->valuesPart;
    }

    public function supportedClausesIds() {
        return InsertQuery::C_DATASET
               | InsertQuery::C_FIELDS
               | InsertQuery::C_VALUES;
    }

    public function stringifyRaw(&$params) {
        $query = $this->insertQuery();
        $table = $query->tableClause();
        if ($table->isEmpty()) throw new \LogicException('Table is not selected');

        if (count($this->valuesPart()) > 1) {
            return $this->strMultiValuesInsert($params);
        }

        return $this->strStdInsert($params);
    }

    public function queryTableClause() {
        return $this->insertQuery()->tableClause();
    }

    protected function strMultiValuesInsert(&$params) {
        $sql = $this->strStdInsertIntoClause($params);

        $valuesStr = [];
        foreach ($this->valuesPart() as $row) {
            $valuesStr[] = $row->embed($params);
        }

        return $sql . ' VALUES ' . implode(', ', $valuesStr);
    }

    protected function strStdInsert(&$params) {
        $query = $this->insertQuery();
        $this->applyInsertedIdClause($query->insertedIdClause());

        $vals = $this->valuesPart()[0];

        return $this->strStdInsertIntoClause($params)
               . $this->strStdValuesClause($vals, $params)
               . $this->strOnDupKeyClause($query->onDupKeyClause(), $params)
               . $this->strReturnIntoClause($query->returnIntoClause(), $params);
    }

    protected function buildFieldsPart() {
        $fields = $this->insertQuery()->fieldsClause()->items();

        return $this->fieldListToPart($fields);
    }

    protected function buildValuesPart() {
        $query = $this->insertQuery();
        $fieldsClause = $query->fieldsClause();
        if ($fieldsClause->isEmpty()) {
            $mainTbl = $query->tableClause()->mainTableModel();
            $fields = $mainTbl ? $mainTbl->fields()->names() : [];
        } else {
            $fields = $fieldsClause->items();
        }

        $values = $query->valuesClause()->items();

        return $this->valueListToPart($values, $fields);
    }

    protected function buildFieldsValuesFromDataset() {
        [$fields, $values] = $this->insertQuery()->datasetClause()->split();

        return [
            $this->fieldListToPart($fields),
            $this->valueListToPart([$values], $fields),
        ];
    }

    /**
     * @param $fields
     *
     * @return PlainSql
     */
    protected function fieldListToPart($fields) {
        $sql = '';
        $params = [];
        if ($fields) {
            $sql = " (" . $this->strColumnList($fields, $params) . ")";
        }

        return $this->createPlainSql($sql, $params);
    }

    /**
     * @param array[] $values
     * @param array   $fields
     *
     * @return array
     */
    protected function valueListToPart($values, $fields) {
        foreach ($values as &$row) {
            $params = [];
            if ($row instanceof \VV\Db\Sql\SelectQuery) {
                $row = $this->factory()
                    ->createSelectStringifier($row)
                    ->stringifyRaw($params);
            } else {
                foreach ($row as $i => &$val) {
                    $field = $fields[$i] ?? null;
                    $val = $this->strValueToSave($val, $field, $params);
                }
                unset($val);
                $row = '(' . implode(', ', $row) . ')';
            }

            $row = $this->createPlainSql($row, $params);
        }
        unset($row);

        return $values;
    }

    /**
     * @return bool
     */
    protected function isStdFieldsValues() {
        return !$this->insertQuery()->valuesClause()->isEmpty();
    }

    /**
     * @param Sql\Clauses\Dataset $ondupkey
     * @param                    $params
     *
     * @return string
     */
    protected function strOnDupKeyClause(Sql\Clauses\Dataset $ondupkey, &$params) {
        if ($ondupkey->isEmpty()) return '';
        throw new \LogicException('onDupKey is not supported by this stringifier');
    }

    /**
     * @param Sql\Clauses\InsertedId $retinsId
     */
    protected function applyInsertedIdClause(Sql\Clauses\InsertedId $retinsId) {
        if ($retinsId->isEmpty()) return;
        throw new \LogicException('InsertedIdClause is not supported by this stringifier');
    }

    /**
     * @param $params
     *
     * @return string
     */
    protected function strStdInsertIntoClause(&$params) {
        $fields = $this->fieldsPart();
        $table = $this->buildTableSql($this->insertQuery()->tableClause());

        return "INSERT INTO {$table->embed($params)} {$fields->embed($params)}";
    }

    protected function useAliasForTable(Sql\Clauses\Table $table) {
        return false;
    }


    private function fillFieldsValuesFromDataset() {
        [$this->fieldsPart, $this->valuesPart] = $this->buildFieldsValuesFromDataset();
    }
}
