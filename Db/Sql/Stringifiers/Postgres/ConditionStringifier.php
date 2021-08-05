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
 * Class ConditionStringifier
 *
 * @package VV\Db\Postgres\QueryStringifiers
 */
class ConditionStringifier extends \VV\Db\Sql\Stringifiers\ConditionStringifier
{

    protected function stringifyPreparedLike(
        string $leftStr,
        string $rightStr,
        string $notStr,
        bool $caseInsensitive
    ): string {
        $operator = $caseInsensitive ? 'ILIKE' : 'LIKE';

        return "$leftStr {$notStr}$operator $rightStr";
    }
}
