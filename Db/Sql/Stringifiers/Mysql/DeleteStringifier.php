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

use VV\Db\Sql\Clauses\DeleteTablesClause;
use VV\Db\Sql\DeleteQuery;

/**
 * Class Delete
 *
 * @package VV\Db\Driver\Mysql\SqlStringifier
 */
class DeleteStringifier extends \VV\Db\Sql\Stringifiers\DeleteStringifier
{

    /**
     * @inheritDoc
     */
    public function getSupportedClausesIds(): int
    {
        return parent::getSupportedClausesIds() | DeleteQuery::C_DEL_TABLES;
    }

    /**
     * @inheritDoc
     */
    protected function stringifyDeleteClause(DeleteTablesClause $tables, ?array &$params): string
    {
        $str = 'DELETE';

        if (!$tables->isEmpty()) {
            $tableStr = [];
            $exprStringifier = $this->getExpressionStringifier();
            foreach ($tables->getItems() as $item) {
                $tableStr[] = $exprStringifier->stringifyDbObject($item, $params);
            }
            $str .= ' ' . implode(', ', $tableStr);
        }

        return $str;
    }
}
