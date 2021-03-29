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
use VV\Db\Sql\DeleteQuery as DeleteQuery;

/**
 * Class DeleteStringifier
 *
 * @package VV\Db\Driver\Sql\Stringifier
 */
class DeleteStringifier extends ModificatoryStringifier {

    private DeleteQuery $deleteQuery;

    /**
     * Delete constructor.
     *
     * @param DeleteQuery $deleteQuery
     * @param Driver      $driver
     */
    public function __construct(DeleteQuery $deleteQuery, Driver $driver) {
        parent::__construct($driver);
        $this->deleteQuery = $deleteQuery;
    }

    /**
     * @return DeleteQuery
     */
    public function deleteQuery() {
        return $this->deleteQuery;
    }

    public function supportedClausesIds() {
        return DeleteQuery::C_TABLE
               | DeleteQuery::C_WHERE;
    }

    public function stringifyRaw(&$params) {
        $query = $this->deleteQuery();
        $this->checkQueryToStr($query);

        $sql = $this->strDeleteClause($query->delTablesClause(), $params)
               . $this->strTableClause($query->tableClause(), $params)
               . $this->strWhereClause($query->whereClause(), $params);

        return $sql;
    }

    protected function strDeleteClause(Sql\Clauses\DeleteTables $tables, &$params) {
        return 'DELETE';
    }

    protected function strTableClause(Sql\Clauses\Table $table, &$params) {
        return ' FROM ' . $this->buildTableSql($table)->embed($params);
    }

    /**
     * @return Sql\Clauses\Table
     */
    public function queryTableClause() {
        return $this->deleteQuery()->tableClause();
    }

    /**
     * @param $query
     */
    protected function checkQueryToStr(DeleteQuery $query) {
        $checkEmptyMap = [
            [$table = $query->tableClause(), '&Table is not selected'],
            [$where = $query->whereClause(), '&There is no where clause'],
        ];
        /** @var Sql\Clauses\Clause $c */
        foreach ($checkEmptyMap as [$c, $m]) {
            if ($c->isEmpty()) throw new \InvalidArgumentException($m);
        }
    }

    protected function useAliasForTable(Sql\Clauses\Table $table) {
        return count($table->items()) > 1;
    }
}
