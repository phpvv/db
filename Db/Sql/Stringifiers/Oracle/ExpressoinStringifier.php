<?php declare(strict_types=1);

/*
 * This file is part of the VV package.
 *
 * (c) Volodymyr Sarnytskyi <v00v4n@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
namespace VV\Db\Sql\Stringifiers\Oracle;

/**
 * Class Expr
 *
 * @package VV\Db\Driver\Oracle\SqlStringifier
 */
class ExpressoinStringifier extends \VV\Db\Sql\Stringifiers\ExpressoinStringifier
{

    /**
     * @inheritDoc
     */
    public function strParam($param, &$params)
    {
        $res = parent::strParam($param, $params);

        $dbp = $param instanceof \VV\Db\Sql\Expressions\SqlParam ? $param->param() : $param;
        if ($dbp instanceof \VV\Db\Param) {
            if (!$dbp->getName()) {
                $dbp->setNextName();
            }

            return ':' . $dbp->getName();
        }

        return $res;
    }

}
