<?php declare(strict_types=1);

/*
 * This file is part of the VV package.
 *
 * (c) Volodymyr Sarnytskyi <v00v4n@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
namespace VV\Db\Sql;

use VV\Db\Sql\Clauses\QueryDatasetTrait;
use VV\Db\Sql\Clauses\QueryWhereTrait;
use VV\Db\Sql\Expressions\Expression;

/**
 * Class Update
 *
 * @package VV\Db\Sql\Query
 */
class UpdateQuery extends ModificatoryQuery {

    use QueryDatasetTrait;
    use QueryWhereTrait;

    public const C_TABLE = 0x01,
        C_DATASET = 0x02,
        C_WHERE = 0x04,
        C_RETURN_INTO = 0x08,
        C_HINT = 0x10;

    /**
     * Sets to null all fields in argument list
     *
     * @param string|Expression ...$fields
     *
     * @return $this
     */
    public function setNull(string|Expression ...$fields): static {
        return $this->set(array_fill_keys($fields, null));
    }

    /** @noinspection PhpArrayShapeAttributeCanBeAddedInspection */
    protected function nonEmptyClausesMap(): array {
        return [
            self::C_TABLE => $this->tableClause(),
            self::C_DATASET => $this->datasetClause(),
            self::C_WHERE => $this->whereClause(),
            self::C_RETURN_INTO => $this->returnIntoClause(),
            self::C_HINT => $this->hintClause(),
        ];
    }
}
