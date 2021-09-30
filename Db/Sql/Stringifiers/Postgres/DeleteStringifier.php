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

namespace VV\Db\Sql\Stringifiers\Postgres;

use VV\Db\Sql\Clauses\TableClause;
use VV\Db\Sql\ModificatoryQuery;

/**
 * Class DeleteStringifier
 *
 * @package VV\Db\Postgres\QueryStringifiers
 */
class DeleteStringifier extends \VV\Db\Sql\Stringifiers\DeleteStringifier
{
    use CommonUtils;

    /**
     * @inheritDoc
     */
    public function getSupportedClausesIds(): int
    {
        return parent::getSupportedClausesIds() | ModificatoryQuery::C_RETURNING;
    }

    protected function useAliasForTable(TableClause $table): bool
    {
        return true;
    }
}
