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
 * Class Oracle
 *
 * @package VV\Db\ModelGenerator\StructBuilder
 */
class Oracle implements \VV\Db\Model\Generator\StructBuilder {

    public function objectIterator(\VV\Db\Connection $connection): iterable {
        // todo: variable to properties and setters
        $tblPrefix = 'tbl_';
        $vwPrefix = 'vw_';

        $tables = $connection->query(
            <<<SQL
SELECT object_id, object_name 
FROM user_objects 
WHERE 
    object_type IN ('TABLE', 'VIEW') 
    AND (
        object_name LIKE '$tblPrefix%'
        OR 
        object_name LIKE '$vwPrefix%'
    ) 
SQL
        )->assoc;

        $typed = ModelGenerator::buildTypeDecorator([
                'NUM' => 'number',
                'TEXT' => 'clob',
                'BLOB' => 'blob',
                'BIN' => 'raw',
                'DATETIME' => ['TIMESTAMP', 'DATE'],
            ]
        );

        foreach ($tables as $table) {
            $type = (stripos($table, $vwPrefix) === 0) ? 'View' : 'Table';
            $objectInfo = new ObjectInfo(strtolower($table), $type);

            $pks = $connection->query('SELECT column_name
                FROM all_constraints cons, all_cons_columns cols
                WHERE cols.table_name = :p1 AND cons.constraint_type = \'P\' AND cons.owner = :p2 AND
                      cons.constraint_name = cols.constraint_name AND cons.owner = cols.owner
                ORDER BY cols.table_name, cols.position', [$table, $connection->user()]
            )->assoc;

            $result = $connection->query(
                'SELECT column_name, data_type, data_length, data_precision, data_scale, data_default, nullable FROM all_tab_columns WHERE table_name = :p1 AND owner = :p2 ORDER BY column_id',
                [$table, $connection->user()]
            );

            foreach ($result as $row) {
                $default = $row['data_default'];
                if ($default != null) {
                    $default = trim((string)$default);
                }

                $objectInfo->addColumn(
                    name: strtolower($name = $row['column_name']),
                    type: $typed($row['data_type']),
                    length: (int)$row['data_length'],
                    intSize: null,
                    precision: (int)$row['data_precision'],
                    scale: (int)$row['data_scale'],
                    default: $default,
                    notnull: $row['nullable'] == 'N',
                    unsigned: false,
                    inpk: in_array($name, $pks)
                );
            }

            yield $objectInfo;
        }
    }
}
