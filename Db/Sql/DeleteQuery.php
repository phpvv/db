<?php declare(strict_types=1);
/** @noinspection PhpUnnecessaryFullyQualifiedNameInspection */

/*
 * This file is part of the VV package.
 *
 * (c) Volodymyr Sarnytskyi <v00v4n@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
namespace VV\Db\Sql;

/**
 * Class DeleteQuery
 *
 * @package VV\Db\Sql
 */
class DeleteQuery extends ModificatoryQuery {

    const C_DEL_TABLES = 0x01,
        C_TABLE = 0x02,
        C_WHERE = 0x04,
        C_RETURN_INTO = 0x08,
        C_HINT = 0x10;

    protected $delTablesClause;


    /**
     * Returns delTablesClause
     *
     * @return Clauses\DeleteTables
     */
    public function delTablesClause() {
        if (!$this->delTablesClause) {
            $this->setDelTablesClause($this->createDelTablesClause());
        }

        return $this->delTablesClause;
    }

    /**
     * Sets delTablesClause
     *
     * @param Clauses\DeleteTables $delTablesClause
     *
     * @return $this
     */
    public function setDelTablesClause(Clauses\DeleteTables $delTablesClause = null) {
        $this->delTablesClause = $delTablesClause;

        return $this;
    }

    /**
     * Clears delTablesClause property and returns previous value
     *
     * @return Clauses\DeleteTables
     */
    public function clearDelTablesClause() {
        try {
            return $this->delTablesClause();
        } finally {
            $this->setDelTablesClause(null);
        }
    }

    /**
     * Creates default delTablesClause
     *
     * @return Clauses\DeleteTables
     */
    public function createDelTablesClause() {
        return new Clauses\DeleteTables;
    }

    /**
     * Add list of tables which need to be deleted
     *
     * @param string[]|\VV\Db\Sql\DbObject[] $tables
     *
     * @return $this
     */
    public function tables(...$tables) {
        $clause = $tables[0] instanceof Clauses\DeleteTables
            ? $tables[0]
            : $this->createDelTablesClause()->add(...$tables);

        return $this->setDelTablesClause($clause);
    }

    /**
     * Add from clause in sql
     *
     * @param string|\VV\Db\Model\Table $tbl
     * @param string                    $alias
     *
     * @return $this
     */
    public function from($tbl, $alias = null) {
        return $this->table($tbl, $alias);
    }

    protected function nonEmptyClausesMap(): array {
        return [
            self::C_DEL_TABLES => $this->delTablesClause(),
            self::C_TABLE => $this->tableClause(),
            self::C_WHERE => $this->whereClause(),
            self::C_RETURN_INTO => $this->returnIntoClause(),
            self::C_HINT => $this->hintClause(),
        ];
    }
}
