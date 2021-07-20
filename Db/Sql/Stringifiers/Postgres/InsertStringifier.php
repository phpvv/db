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

use VV\Db\Sql\Clauses\InsertedIdClause;

/**
 * Class InsertStringifier
 *
 * @package VV\Db\Postgres\QueryStringifiers
 */
class InsertStringifier extends \VV\Db\Sql\Stringifiers\InsertStringifier
{

    use CommonUtils;

    private ?\VV\Db\Param $insertedIdParam = null;

    private ?string $insertedIdField = null;

    public function supportedClausesIds()
    {
        return parent::supportedClausesIds()
               | \VV\Db\Sql\InsertQuery::C_RETURN_INS_ID;
    }

    protected function applyInsertedIdClause(InsertedIdClause $retinsId)
    {
        if ($retinsId->isEmpty()) {
            return;
        }

        $this->insertedIdParam = ($retinsId->getParam() ?: \VV\Db\Param::chr())->setForInsertedId();
        $this->insertedIdField = $retinsId->getPk() ?: $this->insertQuery()->getMainTablePk();
    }

    protected function strStdInsert(&$params)
    {
        $sql = parent::strStdInsert($params);

        if ($this->insertedIdParam) {
            $params[] = $this->insertedIdParam;
            $sql .= " RETURNING $this->insertedIdField AS _insertedid";
        }

        return $sql;
    }

}
