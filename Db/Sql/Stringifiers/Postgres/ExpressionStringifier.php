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

use VV\Db\Param;
use VV\Db\Sql\Expressions\SqlParam;

/**
 * Class ExpressionStringifier
 *
 * @package VV\Db\Sql\Stringifiers\Postgres
 */
class ExpressionStringifier extends \VV\Db\Sql\Stringifiers\ExpressionStringifier
{

    /**
     * @inheritDoc
     */
    public function stringifyParam(mixed $param, ?array &$params): string
    {
        $res = parent::stringifyParam($param, $params);

        $dbp = $param instanceof SqlParam ? $param->getParam() : $param;
        if ($dbp instanceof Param) {
            if (!$dbp->getName()) {
                $dbp->setNextName();
            }

            return ':' . $dbp->getName();
        }

        return $res;
    }
}
