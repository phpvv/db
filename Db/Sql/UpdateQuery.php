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

namespace VV\Db\Sql;

use VV\Db\Model\Table;
use VV\Db\Sql\Clauses\QueryDatasetTrait;
use VV\Db\Sql\Clauses\QueryWhereTrait;
use VV\Db\Sql\Expressions\Expression;

/**
 * Class Update
 *
 * @package VV\Db\Sql\Query
 */
class UpdateQuery extends ModificatoryQuery
{
    use QueryDatasetTrait;
    use QueryWhereTrait;

    public const C_TABLE = 0x01,
        C_DATASET = 0x02,
        C_WHERE = 0x04;

    /**
     * Add from clause in sql
     *
     * @param string|Table|Expression $table
     * @param string|null             $alias
     *
     * @return $this
     */
    public function table(string|Table|Expression $table, string $alias = null): static
    {
        return $this->setMainTable($table, $alias);
    }

    /**
     * Sets to null all columns in argument list
     *
     * @param string|Expression ...$columns
     *
     * @return $this
     */
    public function setNull(string|Expression ...$columns): static
    {
        return $this->set(array_fill_keys($columns, null));
    }

    protected function getNonEmptyClausesMap(): array
    {
        return parent::getNonEmptyClausesMap()
               + [
                   self::C_TABLE => $this->getTableClause(),
                   self::C_DATASET => $this->getDatasetClause(),
                   self::C_WHERE => $this->getWhereClause(),
               ];
    }
}
