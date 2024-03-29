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
use VV\Db\Sql\Clauses\DeleteTablesClause;
use VV\Db\Sql\Clauses\QueryWhereTrait;
use VV\Db\Sql\Expressions\Expression;

/**
 * Class DeleteQuery
 *
 * @package VV\Db\Sql
 */
class DeleteQuery extends ModificatoryQuery
{
    use QueryWhereTrait;

    public const C_TABLE = 0x01,
        C_DEL_TABLES = 0x02,
        C_WHERE = 0x04;

    protected ?DeleteTablesClause $deleteTablesClause = null;

    /**
     * Returns delTablesClause
     *
     * @return DeleteTablesClause
     */
    public function getDeleteTablesClause(): DeleteTablesClause
    {
        if (!$this->deleteTablesClause) {
            $this->setDeleteTablesClause($this->createDeleteTablesClause());
        }

        return $this->deleteTablesClause;
    }

    /**
     * Sets delTablesClause
     *
     * @param DeleteTablesClause|null $deleteTablesClause
     *
     * @return $this
     */
    public function setDeleteTablesClause(?DeleteTablesClause $deleteTablesClause): static
    {
        $this->deleteTablesClause = $deleteTablesClause;

        return $this;
    }

    /**
     * Creates default delTablesClause
     *
     * @return DeleteTablesClause
     */
    public function createDeleteTablesClause(): DeleteTablesClause
    {
        return new DeleteTablesClause();
    }

    /**
     * Add list of tables which need to be deleted
     *
     * @param string|Expression ...$tables
     *
     * @return $this
     */
    public function tables(string|Expression ...$tables): static
    {
        $clause = $this->createDeleteTablesClause()->add(...$tables);

        return $this->setDeleteTablesClause($clause);
    }

    /**
     * Add from clause in sql
     *
     * @param string|Table $table
     * @param string|null  $alias
     *
     * @return $this
     */
    public function from(string|Table $table, string $alias = null): static
    {
        return $this->setMainTable($table, $alias);
    }

    protected function getNonEmptyClausesMap(): array
    {
        return parent::getNonEmptyClausesMap()
               + [
                   self::C_DEL_TABLES => $this->getDeleteTablesClause(),
                   self::C_TABLE => $this->getTableClause(),
                   self::C_WHERE => $this->getWhereClause(),
               ];
    }
}
