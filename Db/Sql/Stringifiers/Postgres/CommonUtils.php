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

use VV\Db\Sql\Stringifiers\QueryStringifier;

/**
 * Trait CommonUtils
 *
 * @package VV\Db\Postgres\QueryStringifiers
 */
trait CommonUtils
{

    protected function stringifyFinalDecorate($sql)
    {
        $sql = str_replace('"', "'", $sql);

        $sql = preg_replace_callback('/`([\w\$]+)`/',
            function ($m) {
                return '"' . $m[1] . '"';
            },
            $sql
        );

        $sql = preg_replace_callback('/\?/',
            function () {
                static $i = 0;

                return ':p' . (++$i);
            },
            $sql
        );

        return $sql;
    }

    /**
     * @return ExpressoinStringifier
     */
    protected function createExprStringifier()
    {
        /** @var QueryStringifier $this */
        return new ExpressoinStringifier($this);
    }

    protected function createConditionStringifier()
    {
        /** @var QueryStringifier $this */
        return new ConditionStringifier($this);
    }
}
