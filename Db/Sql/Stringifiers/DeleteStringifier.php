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

use VV\Db\Sql;
use VV\Db\Sql\Clauses\DeleteTablesClause;
use VV\Db\Sql\Clauses\TableClause;
use VV\Db\Sql\DeleteQuery;

/**
 * Class DeleteStringifier
 *
 * @package VV\Db\Driver\Sql\Stringifier
 */
class DeleteStringifier extends ModificatoryStringifier
{
    private DeleteQuery $deleteQuery;

    /**
     * DeleteStringifier constructor.
     *
     * @param DeleteQuery $deleteQuery
     * @param Factory     $factory
     */
    public function __construct(DeleteQuery $deleteQuery, Factory $factory)
    {
        parent::__construct($factory);
        $this->deleteQuery = $deleteQuery;
    }

    /**
     * @return DeleteQuery
     */
    public function getDeleteQuery(): DeleteQuery
    {
        return $this->deleteQuery;
    }

    /**
     * @inheritDoc
     */
    public function getSupportedClausesIds(): int
    {
        return DeleteQuery::C_TABLE | DeleteQuery::C_WHERE;
    }

    /**
     * @inheritDoc
     */
    public function stringifyRaw(?array &$params): string
    {
        $query = $this->getDeleteQuery();
        $this->checkQueryToStringify($query);

        return $this->stringifyDeleteClause($query->getDeleteTablesClause(), $params)
               . $this->stringifyTableClause($query->getTableClause(), $params)
               . $this->stringifyWhereClause($query->getWhereClause(), $params)
               . $this->stringifyReturningClause($query->getReturningClause(), $params);
    }

    /**
     * @inheritDoc
     */
    public function getQueryTableClause(): TableClause
    {
        return $this->getDeleteQuery()->getTableClause();
    }

    /**
     * @param DeleteTablesClause $tables
     * @param array|null         $params
     *
     * @return string
     */
    protected function stringifyDeleteClause(DeleteTablesClause $tables, ?array &$params): string
    {
        return 'DELETE';
    }

    /**
     * @param TableClause $table
     * @param array|null  $params
     *
     * @return string
     */
    protected function stringifyTableClause(TableClause $table, ?array &$params): string
    {
        return ' FROM ' . $this->buildTableSql($table)->embed($params);
    }

    /**
     * @param DeleteQuery $query
     */
    protected function checkQueryToStringify(DeleteQuery $query)
    {
        $checkEmptyMap = [
            [$query->getTableClause(), 'Table is not selected'],
            [$query->getWhereClause(), 'There is no where clause'],
        ];
        /** @var Sql\Clauses\Clause $c */
        foreach ($checkEmptyMap as [$c, $m]) {
            if ($c->isEmpty()) {
                throw new \InvalidArgumentException($m);
            }
        }
    }

    /**
     * @inheritDoc
     */
    protected function useAliasForTable(TableClause $table): bool
    {
        return count($table->getItems()) > 1;
    }
}
