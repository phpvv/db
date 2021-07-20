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

/**
 * Class ExpressoinStringifier
 *
 * @package VV\Db\Postgres\QueryStringifiers
 */
class ExpressoinStringifier extends \VV\Db\Sql\Stringifiers\ExpressoinStringifier
{

    /**
     * @inheritDoc
     */
    public function strParam($param, &$params)
    {
        $res = parent::strParam($param, $params);

        $dbp = $param instanceof \VV\Db\Sql\Expressions\SqlParam ? $param->getParam() : $param;
        if ($dbp instanceof \VV\Db\Param) {
            if (!$dbp->getName()) {
                $dbp->setNextName();
            }

            return ':' . $dbp->getName();
        }

        return $res;
    }

}
