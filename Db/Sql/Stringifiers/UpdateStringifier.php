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

use VV\Db\Sql\Clauses\Clause;
use VV\Db\Sql\Clauses\DatasetClause;
use VV\Db\Sql\Clauses\TableClause;
use VV\Db\Sql\UpdateQuery;

/**
 * Class Update
 *
 * @package VV\Db\Driver\Sql\Stringifier
 */
class UpdateStringifier extends ModificatoryStringifier
{
    private UpdateQuery $updateQuery;

    /**
     * UpdateStringifier constructor.
     *
     * @param UpdateQuery $updateQuery
     * @param Factory     $factory
     */
    public function __construct(UpdateQuery $updateQuery, Factory $factory)
    {
        parent::__construct($factory);
        $this->updateQuery = $updateQuery;
    }

    /**
     * @return UpdateQuery
     */
    public function getUpdateQuery(): UpdateQuery
    {
        return $this->updateQuery;
    }

    /**
     * @inheritDoc
     */
    public function getSupportedClausesIds(): int
    {
        return UpdateQuery::C_TABLE | UpdateQuery::C_DATASET | UpdateQuery::C_WHERE;
    }

    /**
     * @inheritDoc
     */
    public function stringifyRaw(?array &$params): string
    {
        $query = $this->getUpdateQuery();
        $this->checkQueryToStringify($query);

        return $this->stringifyUpdateClause($query->getTableClause(), $params)
               . $this->stringifySetClause($query->getDatasetClause(), $params)
               . $this->stringifyWhereClause($query->getWhereClause(), $params)
               . $this->stringifyReturnIntoClause($query->getReturnIntoClause(), $params)
               . $this->stringifyReturningClause($query->getReturningClause(), $params);
    }

    /**
     * @inheritDoc
     */
    public function getQueryTableClause(): TableClause
    {
        return $this->getUpdateQuery()->getTableClause();
    }

    /**
     * @param TableClause $table
     * @param array|null  $params
     *
     * @return string
     */
    protected function stringifyUpdateClause(TableClause $table, ?array &$params): string
    {
        return 'UPDATE ' . $this->buildTableSql($table)->embed($params);
    }

    /**
     * @param DatasetClause $dataset
     * @param array|null    $params
     *
     * @return string
     */
    protected function stringifySetClause(DatasetClause $dataset, ?array &$params): string
    {
        return ' SET ' . $this->stringifyDataset($dataset, $params);
    }

    /**
     * @param UpdateQuery $query
     *
     * @return array
     */
    protected function checkQueryToStringify(UpdateQuery $query): array
    {
        $checkEmptyMap = [
            [$table = $query->getTableClause(), 'Table is not selected'],
            [$set = $query->getDatasetClause(), 'There is no data to update'],
            [$where = $query->getWhereClause(), 'There is no where clause'],
        ];
        /** @var Clause $c */
        foreach ($checkEmptyMap as [$c, $m]) {
            if ($c->isEmpty()) {
                throw new \InvalidArgumentException($m);
            }
        }

        return [$table, $set, $where];
    }
}
