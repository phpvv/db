<?php declare(strict_types=1);

/*
 * This file is part of the VV package.
 *
 * (c) Volodymyr Sarnytskyi <v00v4n@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
namespace VV\Db\Sql\Clauses;

use VV\Db\Sql\DbObject;
use VV\Db\Sql\Expression;

/**
 * Class DeleteTablesClause
 *
 * @package VV\Db\Sql\Clauses
 * @method DbObject[] items():array
 */
class DeleteTablesClause extends ItemList {

    /**
     * Add field(s)
     *
     * @param string|Expression ...$tables
     *
     * @return $this
     */
    public function add(...$tables): static {
        if (!$tables) return $this;

        foreach ($tables as $i => $tbl) {
            if (!$tbl = DbObject::create($tbl)) {
                throw new \InvalidArgumentException("Table #$i is incorrect");
            }

            $this->appendItems($tbl);
        }

        return $this;
    }
}
