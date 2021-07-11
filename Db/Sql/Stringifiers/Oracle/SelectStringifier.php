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
 * Class Select
 *
 * @package VV\Db\Driver\Oracle\SqlStringifier
 */
class SelectStringifier extends \VV\Db\Sql\Stringifiers\SelectStringifier
{

    use CommonUtils;

    protected function applyLimitClause(&$sql, int $count, int $offset): void
    {
        $rn = 'ora_rownum_field';
        $fields = '`' . implode('`, `', $this->selectQuery()->columnsClause()->resultFields()) . '`';
        $sql = "SELECT $fields FROM (SELECT t.*, rownum AS $rn FROM ($sql) t) WHERE $rn>$offset AND $rn<=$count+$offset";
    }

    protected function applyOderByItemNullsLast(&$str, $colstr, \VV\Db\Sql\Clauses\OrderByClauseItem $item): void
    {
        $str .= ' NULLS ' . ($item->isNullsLast() ? 'LAST' : 'FIRST');
    }
}
