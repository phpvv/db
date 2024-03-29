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

use VV\Db\Param;
use VV\Db\Sql\Stringifiers\SqlPart;

/**
 * Trait ModifyUtils
 *
 * @package VV\Db\Driver\Oracle\SqlStringifier
 */
trait ModifyUtils
{

    /**
     * @inheritDoc
     */
    protected function expressionValueToSave(mixed $value, mixed $column): ?SqlPart
    {
        if ($value instanceof Param && $value->isLob()) {
            $type = $value->getType();
            if ($type == Param::T_TEXT) {
                $emptyLobFunc = 'empty_clob()';
            } elseif ($type == Param::T_BLOB) {
                $emptyLobFunc = 'empty_blob()';
            } else {
                throw new \UnexpectedValueException();
            }

            $value->setForUpload(true);
            $this->addExtraReturnInto($column, $value);

            return $this->createSqlPart($emptyLobFunc);
        }

        /** @noinspection PhpMultipleClassDeclarationsInspection */
        return parent::expressionValueToSave($value, $column);
    }
}
