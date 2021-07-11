<?php declare(strict_types=1);

/*
 * This file is part of the VV package.
 *
 * (c) Volodymyr Sarnytskyi <v00v4n@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
namespace VV\Db\Model\Generator\StructBuilder;

use VV\Db\Model\Generator\ModelGenerator;
use VV\Db\Model\Generator\ObjectInfo;

/**
 * Class Mysql
 *
 * @package VV\Db\ModelGenerator\StructBuilder
 */
class Mysql implements \VV\Db\Model\Generator\StructBuilder
{

    public function objectIterator(\VV\Db\Connection $connection): iterable
    {
        $tables = $connection->query('SHOW TABLES')->rows(\VV\Db::FETCH_ASSOC);

        $typed = ModelGenerator::buildTypeDecorator([
                'NUM' => ['.*int', 'decimal', 'integer', 'numeric', 'double', 'float', 'real'],
                'TEXT' => '.*text',
                'BLOB' => '.*blob',
                'DATETIME' => ['timestamp', 'datetime'],
                'DATE' => ['date', 'year'],
                'TIME' => 'time',
            ]
        );

        foreach ($tables as $v) {
            $table = current($v);
            $objectInfo = new ObjectInfo($table, 'Table');

            $result = $connection->query('SHOW COLUMNS FROM ' . $table);
            foreach ($result as $row) {
                preg_match('/(\w+)(?:\((\d+)\))?(?: (\w+))?/', $row['Type'], $m);
                $m = \VV\aget(range(0, 3), $m);

                $l = explode(',', $m[2]);

                $dataType = $m[1];
                $type = $typed($dataType);
                $intSize = match ($dataType) {
                    'bigint' => 8,
                    'int' => 4,
                    'smallint' => 2,
                    'tinyint' => 1,
                    default => null,
                };

                $objectInfo->addColumn(
                    name: $row['Field'],
                    type: $type,
                    length: (int)$l[0],
                    intSize: $intSize,
                    precision: (int)$l[0],
                    scale: $l[1] ?? null,
                    default: $row['Default'],
                    notnull: $row['Null'] == 'NO',
                    unsigned: $m[3] == 'unsigned',
                    inpk: $row['Key'] == 'PRI',
                );
            }

            yield $objectInfo;
        }
    }
}
