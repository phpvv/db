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

/**
 * Class InsertStringifier
 *
 * @package VV\Db\Postgres\QueryStringifiers
 */
class InsertStringifier extends \VV\Db\Sql\Stringifiers\InsertStringifier
{
    use CommonUtils;

    private ?Param $insertedIdParam = null;
    private ?string $insertedIdField = null;

    /**
     * @inheritDoc
     */
    public function getSupportedClausesIds(): int
    {
        return parent::getSupportedClausesIds() | InsertQuery::C_RETURN_INS_ID;
    }

    /**
     * @inheritDoc
     */
    protected function applyInsertedIdClause(InsertedIdClause $insertedIdClause)
    {
        if ($insertedIdClause->isEmpty()) {
            return;
        }

        $this->insertedIdParam = ($insertedIdClause->getParam() ?: Param::chr())->setForInsertedId();
        $this->insertedIdField = $insertedIdClause->getPk() ?: $this->insertQuery()->getMainTablePk();
    }

    /**
     * @inheritDoc
     */
    protected function stringifyStdInsert(?array &$params): string
    {
        $sql = parent::stringifyStdInsert($params);

        if ($this->insertedIdParam) {
            $params[] = $this->insertedIdParam;
            $sql .= " RETURNING $this->insertedIdField AS _insertedid";
        }

        return $sql;
    }
}
