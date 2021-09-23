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

namespace VV\Db\Sql\Stringifiers\Postgres;

use VV\Db\Param;
use VV\Db\Sql\Clauses\InsertedIdClause;
use VV\Db\Sql\InsertQuery;
use VV\Db\Sql\ModificatoryQuery;

/**
 * Class InsertStringifier
 *
 * @package VV\Db\Postgres\QueryStringifiers
 */
class InsertStringifier extends \VV\Db\Sql\Stringifiers\InsertStringifier
{
    use CommonUtils;

    private ?Param $insertedIdParam = null;
    private ?string $insertedIdColumn = null;

    /**
     * @inheritDoc
     */
    public function getSupportedClausesIds(): int
    {
        return parent::getSupportedClausesIds() | InsertQuery::C_RETURN_INSERTED_ID | ModificatoryQuery::C_RETURNING;
    }

    /**
     * @param array|null &$params
     *
     * @inheritDoc
     */
    protected function applyInsertedIdClause(InsertedIdClause $insertedIdClause, ?array &$params)
    {
        if ($insertedIdClause->isEmpty()) {
            return;
        }

        $params[] = ($insertedIdClause->getParam() ?: Param::str())->setForInsertedId();
        $column = $insertedIdClause->getPk() ?: $this->getInsertQuery()->getMainTablePk();

        $this->addExtraReturning("$column _insertedid");
    }
}
