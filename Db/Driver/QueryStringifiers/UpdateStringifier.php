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
use VV\Db\Sql;
use VV\Db\Sql\UpdateQuery as UpdateQuery;

/**
 * Class Update
 *
 * @package VV\Db\Driver\Sql\Stringifier
 */
class UpdateStringifier extends ModificatoryStringifier {

    private UpdateQuery $updateQuery;

    /**
     * Update constructor.
     *
     * @param UpdateQuery $updateQuery
     * @param Driver      $driver
     */
    public function __construct(UpdateQuery $updateQuery, Driver $driver) {
        parent::__construct($driver);
        $this->updateQuery = $updateQuery;
    }

    /**
     * @return UpdateQuery
     */
    public function updateQuery() {
        return $this->updateQuery;
    }

    public function supportedClausesIds() {
        return UpdateQuery::C_TABLE
               | UpdateQuery::C_DATASET
               | UpdateQuery::C_WHERE;
    }

    public function stringifyRaw(&$params) {
        $query = $this->updateQuery();
        $this->checkQueryToStr($query);

        return $this->strUpdateClause($query->tableClause(), $params)
               . $this->strSetClause($query->datasetClause(), $params)
               . $this->strWhereClause($query->whereClause(), $params)
               . $this->strReturnIntoClause($query->returnIntoClause(), $params);
    }

    protected function strUpdateClause(Sql\Clauses\Table $table, &$params) {
        return 'UPDATE ' . $this->buildTableSql($table)->embed($params);
    }

    protected function strSetClause(Sql\Clauses\Dataset $dataset, &$params) {
        return ' SET ' . $this->strDataset($dataset, $params);
    }

    public function queryTableClause() {
        return $this->updateQuery()->tableClause();
    }

    /**
     * @param UpdateQuery $query
     *
     * @return array
     */
    protected function checkQueryToStr(UpdateQuery $query) {
        $checkEmptyMap = [
            [$table = $query->tableClause(), 'Table is not selected'],
            [$set = $query->datasetClause(), 'There is no data to update'],
            [$where = $query->whereClause(), 'There is no where clause'],
        ];
        /** @var Sql\Clauses\Clause $c */
        foreach ($checkEmptyMap as [$c, $m]) {
            if ($c->isEmpty()) throw new \InvalidArgumentException($m);
        }

        return array($table, $set, $where);
    }
}
