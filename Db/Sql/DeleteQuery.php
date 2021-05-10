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

use JetBrains\PhpStorm\Pure;
use VV\Db\Model\Table;
use VV\Db\Sql\Clauses\DeleteTablesClause;
use VV\Db\Sql\Clauses\QueryWhereTrait;
use VV\Db\Sql\Expressions\DbObject;

/**
 * Class DeleteQuery
 *
 * @package VV\Db\Sql
 */
class DeleteQuery extends ModificatoryQuery {

    use QueryWhereTrait;

    public const C_DEL_TABLES = 0x01,
        C_TABLE = 0x02,
        C_WHERE = 0x04,
        C_RETURN_INTO = 0x08,
        C_HINT = 0x10;

    protected ?DeleteTablesClause $delTablesClause = null;

    /**
     * Returns delTablesClause
     *
     * @return DeleteTablesClause
     */
    public function delTablesClause(): DeleteTablesClause {
        if (!$this->delTablesClause) {
            $this->setDelTablesClause($this->createDelTablesClause());
        }

        return $this->delTablesClause;
    }

    /**
     * Sets delTablesClause
     *
     * @param DeleteTablesClause|null $delTablesClause
     *
     * @return $this
     */
    public function setDelTablesClause(?DeleteTablesClause $delTablesClause): static {
        $this->delTablesClause = $delTablesClause;

        return $this;
    }

    /**
     * Clears delTablesClause property and returns previous value
     *
     * @return DeleteTablesClause
     */
    public function clearDelTablesClause(): DeleteTablesClause {
        try {
            return $this->delTablesClause();
        } finally {
            $this->setDelTablesClause(null);
        }
    }

    /**
     * Creates default delTablesClause
     *
     * @return DeleteTablesClause
     */
    #[Pure]
    public function createDelTablesClause(): DeleteTablesClause {
        return new DeleteTablesClause;
    }

    /**
     * Add list of tables which need to be deleted
     *
     * @param string[]|DbObject[] $tables
     *
     * @return $this
     */
    public function tables(...$tables): static {
        $clause = $this->createDelTablesClause()->add(...$tables);

        return $this->setDelTablesClause($clause);
    }

    /**
     * Add from clause in sql
     *
     * @param string|Table $table
     * @param string|null  $alias
     *
     * @return $this
     */
    public function from(string|Table $table, string $alias = null): static {
        return $this->table($table, $alias);
    }

    /** @noinspection PhpArrayShapeAttributeCanBeAddedInspection */
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
