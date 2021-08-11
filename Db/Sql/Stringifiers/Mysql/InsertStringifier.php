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

namespace VV\Db\Sql\Stringifiers\Mysql;

use VV\Db\Sql\Clauses\DatasetClause;
use VV\Db\Sql\Clauses\InsertedIdClause;
use VV\Db\Sql\InsertQuery;

/**
 * Class Insert
 *
 * @package VV\Db\Driver\Mysql\SqlStringifier
 */
class InsertStringifier extends \VV\Db\Sql\Stringifiers\InsertStringifier
{

    /**
     * @inheritDoc
     */
    public function getSupportedClausesIds(): int
    {
        return parent::getSupportedClausesIds() | InsertQuery::C_ON_DUPLICATE_KEY | InsertQuery::C_RETURN_INSERTED_ID;
    }

    /**
     * @inheritDoc
     */
    protected function stringifyOnDupKeyClause(DatasetClause $dataset, ?array &$params): string
    {
        if ($dataset->isEmpty()) {
            return '';
        }

        return ' ON DUPLICATE KEY UPDATE ' . $this->stringifyDataset($dataset, $params);
    }

    /**
     * @param array|null &$params
     *
     * @inheritDoc
     */
    protected function applyInsertedIdClause(InsertedIdClause $insertedIdClause, ?array &$params)
    {
        // empty body
    }
}
