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

/**
 * Trait CommonUtils
 *
 * @package VV\Db\Driver\Oracle\SqlStringifier
 */
trait CommonUtils
{

    protected function stringifyFinalDecorate($sql)
    {
        $sql = str_replace('"', "'", $sql);

        $map = [
            'IFNULL' => 'NVL',
            //'DATE_FORMAT' => 'TO_CHAR',
        ];
        foreach ($map as $k => $v) {
            $sql = preg_replace('/\b' . $k . '\b/', $v, $sql);
        }

        $sql = preg_replace_callback('/`([\w\$]+)`/',
            function ($m) {
                return '"' . strtoupper($m[1]) . '"';
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
        /** @var \VV\Db\Sql\Stringifiers\QueryStringifier $this */
        return new ExpressoinStringifier($this);
    }
}
