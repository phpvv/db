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
use VV\Db\Sql\DeleteQuery as DeleteQuery;

/**
 * Class DeleteStringifier
 *
 * @package VV\Db\Driver\Sql\Stringifier
 */
class DeleteStringifier extends ModificatoryStringifier
{

    private DeleteQuery $deleteQuery;

    /**
     * Delete constructor.
     *
     * @param DeleteQuery $deleteQuery
     * @param Driver      $factory
     */
    public function __construct(DeleteQuery $deleteQuery, Factory $factory)
    {
        parent::__construct($factory);
        $this->deleteQuery = $deleteQuery;
    }

    /**
     * @return DeleteQuery
     */
    public function deleteQuery()
    {
        return $this->deleteQuery;
    }

    public function supportedClausesIds()
    {
        return DeleteQuery::C_TABLE
               | DeleteQuery::C_WHERE;
    }

    public function stringifyRaw(&$params)
    {
        $query = $this->deleteQuery();
        $this->checkQueryToStr($query);

        $sql = $this->strDeleteClause($query->getDeleteTablesClause(), $params)
               . $this->strTableClause($query->getTableClause(), $params)
               . $this->strWhereClause($query->getWhereClause(), $params);

        return $sql;
    }

    /**
     * @return Sql\Clauses\TableClause
     */
    public function queryTableClause()
    {
        return $this->deleteQuery()->getTableClause();
    }

    protected function strDeleteClause(Sql\Clauses\DeleteTablesClause $tables, &$params)
    {
        return 'DELETE';
    }

    protected function strTableClause(Sql\Clauses\TableClause $table, &$params)
    {
        return ' FROM ' . $this->buildTableSql($table)->embed($params);
    }

    /**
     * @param $query
     */
    protected function checkQueryToStr(DeleteQuery $query)
    {
        $checkEmptyMap = [
            [$table = $query->getTableClause(), '&Table is not selected'],
            [$where = $query->getWhereClause(), '&There is no where clause'],
        ];
        /** @var Sql\Clauses\Clause $c */
        foreach ($checkEmptyMap as [$c, $m]) {
            if ($c->isEmpty()) {
                throw new \InvalidArgumentException($m);
            }
        }
    }

    protected function useAliasForTable(Sql\Clauses\TableClause $table)
    {
        return count($table->getItems()) > 1;
    }
}
