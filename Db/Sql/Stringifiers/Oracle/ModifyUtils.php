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
 * Trait ModifyUtils
 *
 * @package VV\Db\Driver\Oracle\SqlStringifier
 */
trait ModifyUtils {

    protected function exprValueToSave($value, $field) {
        if ($value instanceof \VV\Db\Param && $value->isLob()) {
            $type = $value->type();
            if ($type == \VV\Db\Param::T_TEXT) {
                $emtyLobFunc = 'empty_clob()';
            } elseif ($type == \VV\Db\Param::T_BLOB) {
                $emtyLobFunc = 'empty_blob()';
            } else {
                throw new \UnexpectedValueException;
            }

            $value->setForUpload(true);
            $this->addAdvReturnInto($field, $value);

            return $this->createPlainSql($emtyLobFunc);
        }

        /** @noinspection PhpUndefinedClassInspection */
        return parent::exprValueToSave($value, $field);
    }
}
