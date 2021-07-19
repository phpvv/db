<?php declare(strict_types=1);

/*
 * This file is part of the VV package.
 *
 * (c) Volodymyr Sarnytskyi <v00v4n@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
namespace VV\Db\Sql\Stringifiers\Mysql;

use VV\Db\Sql\Clauses\DatasetClause as OnDupKeyClause;
use VV\Db\Sql\InsertQuery as InsertQuery;

/**
 * Class Insert
 *
 * @package VV\Db\Driver\Mysql\SqlStringifier
 */
class InsertStringifier extends \VV\Db\Sql\Stringifiers\InsertStringifier
{

    public function supportedClausesIds()
    {
        return parent::supportedClausesIds()
               | InsertQuery::C_ON_DUP_KEY
               | InsertQuery::C_RETURN_INS_ID;
    }

    /**
     * @param OnDupKeyClause            $ondupkey
     * @param                           $params
     *
     * @return string|void
     */
    protected function strOnDupKeyClause(OnDupKeyClause $ondupkey, &$params)
    {
        if ($ondupkey->isEmpty()) {
            return '';
        }

        return ' ON DUPLICATE KEY UPDATE ' . $this->strDataset($ondupkey, $params);
    }

    /**
     * @inheritDoc
     */
    protected function applyInsertedIdClause(\VV\Db\Sql\Clauses\InsertedIdClause $retinsId)
    {
        if ($retinsId->isEmpty()) {
            return;
        }
    }
}
