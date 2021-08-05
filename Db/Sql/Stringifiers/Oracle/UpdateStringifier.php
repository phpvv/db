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

namespace VV\Db\Sql\Stringifiers\Oracle;

use VV\Db\Sql\UpdateQuery;

/**
 * Class Update
 *
 * @package VV\Db\Driver\Oracle\SqlStringifier
 */
class UpdateStringifier extends \VV\Db\Sql\Stringifiers\UpdateStringifier
{
    use ModifyUtils;
    use CommonUtils;

    /**
     * @inheritDoc
     */
    public function getSupportedClausesIds(): int
    {
        return parent::getSupportedClausesIds() | UpdateQuery::C_RETURN_INTO;
    }
}
