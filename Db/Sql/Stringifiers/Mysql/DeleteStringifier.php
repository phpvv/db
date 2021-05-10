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

use VV\Db\Sql;

/**
 * Class Delete
 *
 * @package VV\Db\Driver\Mysql\SqlStringifier
 */
class DeleteStringifier extends \VV\Db\Sql\Stringifiers\DeleteStringifier {

    public function supportedClausesIds() {
        return parent::supportedClausesIds() | \VV\Db\Sql\DeleteQuery::C_DEL_TABLES;
    }

    protected function strDeleteClause(Sql\Clauses\DeleteTablesClause $tables, &$params) {
        $str = 'DELETE';

        if (!$tables->isEmpty()) {
            $tblstr = [];
            $exprStringifier = $this->exprStringifier();
            foreach ($tables->items() as $item) {
                $tblstr[] = $exprStringifier->strSqlObj($item, $params);
            }
            $str .= ' ' . implode(', ', $tblstr);
        }

        return $str;
    }
}
