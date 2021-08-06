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

    /**
     * @inheritDoc
     */
    protected function stringifyFinalDecorate(string $sql): string
    {
        $sql = str_replace('"', "'", $sql);

        $sql = preg_replace_callback(
            '/`([\w\$]+)`/',
            fn ($m) => '"' . $m[1] . '"',
            $sql
        );

        /** @noinspection PhpUnnecessaryLocalVariableInspection */
        $sql = preg_replace_callback(
            '/\?/',
            function () {
                static $i = 0;

                return ':p' . (++$i);
            },
            $sql
        );

        return $sql;
    }

    /**
     * @inheritDoc
     */
    protected function createExpressionStringifier(): ExpressionStringifier
    {
        /** @var QueryStringifier $this */
        return new ExpressionStringifier($this);
    }

    /**
     * @inheritDoc
     */
    protected function createConditionStringifier(): ConditionStringifier
    {
        /** @var QueryStringifier $this */
        return new ConditionStringifier($this);
    }
}
