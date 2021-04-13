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

/**
 * Class ExpressoinStringifier
 *
 * @package VV\Db\Postgres\QueryStringifiers
 */
class ExpressoinStringifier extends \VV\Db\Sql\Stringifiers\ExpressoinStringifier {

    /**
     * @inheritDoc
     */
    public function strParam($param, &$params) {
        $res = parent::strParam($param, $params);

        $dbp = $param instanceof \VV\Db\Sql\Param ? $param->param() : $param;
        if ($dbp instanceof \VV\Db\Param) {
            if (!$dbp->name()) $dbp->setNextName();

            return ':' . $dbp->name();
        }

        return $res;
    }

}
