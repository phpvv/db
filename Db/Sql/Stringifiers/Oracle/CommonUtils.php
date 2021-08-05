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

use VV\Db\Sql\Stringifiers\ExpressionStringifier;

/**
 * Trait CommonUtils
 *
 * @package VV\Db\Driver\Oracle\SqlStringifier
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
            fn ($m) => '"' . strtoupper($m[1]) . '"',
            $sql
        );

        $i = 0;
        /** @noinspection PhpUnnecessaryLocalVariableInspection */
        $sql = preg_replace_callback(
            '/\?/',
            fn () => ':p' . (++$i),
            $sql
        );

        return $sql;
    }

    /**
     * @inheritDoc
     */
    protected function createExpressionStringifier(): ExpressionStringifier
    {
        /** @var \VV\Db\Sql\Stringifiers\QueryStringifier $this */
        return new ExpressionStringifier($this);
    }
}
