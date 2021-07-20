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

namespace VV\Db\Sql\Clauses;

use VV\Db\Sql\Expressions\DbObject;
use VV\Db\Sql\Expressions\Expression;

/**
 * Class DeleteTablesClause
 *
 * @package VV\Db\Sql\Clauses
 * @method DbObject[] getItems():array
 */
class DeleteTablesClause extends ItemList
{

    /**
     * Add field(s)
     *
     * @param string|Expression ...$tables
     *
     * @return $this
     */
    public function add(string|Expression ...$tables): static
    {
        if (!$tables) {
            return $this;
        }

        foreach ($tables as $i => $tbl) {
            if (!$tbl = DbObject::create($tbl)) {
                throw new \InvalidArgumentException("Table #$i is incorrect");
            }

            $this->appendItems($tbl);
        }

        return $this;
    }
}
