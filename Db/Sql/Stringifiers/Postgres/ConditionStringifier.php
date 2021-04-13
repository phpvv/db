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
 * Class ConditionStringifier
 *
 * @package VV\Db\Postgres\QueryStringifiers
 */
class ConditionStringifier extends \VV\Db\Sql\Stringifiers\ConditionStringifier {

    protected function strPreparedLike(string $lstr, string $rstr, string $notstr, bool $caseInsensitive) {
        $oper = $caseInsensitive ? 'ILIKE' : 'LIKE';

        return "$lstr {$notstr}$oper $rstr";
    }
}
