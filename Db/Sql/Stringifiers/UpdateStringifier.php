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

use VV\Db\Driver\Driver;
use VV\Db\Sql;
use VV\Db\Sql\UpdateQuery as UpdateQuery;

/**
 * Class Update
 *
 * @package VV\Db\Driver\Sql\Stringifier
 */
class UpdateStringifier extends ModificatoryStringifier
{

    private UpdateQuery $updateQuery;

    /**
     * Update constructor.
     *
     * @param UpdateQuery $updateQuery
     * @param Driver      $factory
     */
    public function __construct(UpdateQuery $updateQuery, Factory $factory)
    {
        parent::__construct($factory);
        $this->updateQuery = $updateQuery;
    }

    /**
     * @return UpdateQuery
     */
    public function updateQuery()
    {
        return $this->updateQuery;
    }

    public function supportedClausesIds()
    {
        return UpdateQuery::C_TABLE
               | UpdateQuery::C_DATASET
               | UpdateQuery::C_WHERE;
    }

    public function stringifyRaw(&$params)
    {
        $query = $this->updateQuery();
        $this->checkQueryToStr($query);

        return $this->strUpdateClause($query->getTableClause(), $params)
               . $this->strSetClause($query->getDatasetClause(), $params)
               . $this->strWhereClause($query->getWhereClause(), $params)
               . $this->strReturnIntoClause($query->getReturnIntoClause(), $params);
    }

    public function queryTableClause()
    {
        return $this->updateQuery()->getTableClause();
    }

    protected function strUpdateClause(Sql\Clauses\TableClause $table, &$params)
    {
        return 'UPDATE ' . $this->buildTableSql($table)->embed($params);
    }

    protected function strSetClause(Sql\Clauses\DatasetClause $dataset, &$params)
    {
        return ' SET ' . $this->strDataset($dataset, $params);
    }

    /**
     * @param UpdateQuery $query
     *
     * @return array
     */
    protected function checkQueryToStr(UpdateQuery $query)
    {
        $checkEmptyMap = [
            [$table = $query->getTableClause(), 'Table is not selected'],
            [$set = $query->getDatasetClause(), 'There is no data to update'],
            [$where = $query->getWhereClause(), 'There is no where clause'],
        ];
        /** @var Sql\Clauses\Clause $c */
        foreach ($checkEmptyMap as [$c, $m]) {
            if ($c->isEmpty()) {
                throw new \InvalidArgumentException($m);
            }
        }

        return [$table, $set, $where];
    }
}
