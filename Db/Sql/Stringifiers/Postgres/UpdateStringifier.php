<?php declare(strict_types=1);

/*
 * This file is part of the VV package.
 *
 * (c) Volodymyr Sarnytskyi <v00v4n@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
namespace VV\Db\Sql\Stringifiers\Postgres;

use VV\Db\Sql\UpdateQuery as UpdateQuery;

/**
 * Class UpdateStringifier
 *
 * @package VV\Db\Postgres\QueryStringifiers
 */
class UpdateStringifier extends \VV\Db\Sql\Stringifiers\UpdateStringifier
{

    use CommonUtils;

    public function supportedClausesIds()
    {
        return parent::supportedClausesIds() | UpdateQuery::C_RETURN_INTO;
    }
}
