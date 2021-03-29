<?php declare(strict_types=1);

/*
 * This file is part of the VV package.
 *
 * (c) Volodymyr Sarnytskyi <v00v4n@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
namespace VV\Db\ModelGenerator\StructBuilder;

use VV\Db\ModelGenerator\Generator;
use VV\Db\ModelGenerator\ObjectInfo;

/**
 * Class Oracle
 *
 * @package VV\Db\ModelGenerator\StructBuilder
 */
class Oracle implements \VV\Db\ModelGenerator\StructBuilder {

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

        $typed = Generator::buildTypeDecorator([
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
                    strtolower($name = $row['column_name']),
                    $typed($row['data_type']),
                    (int)$row['data_length'],
                    (int)$row['data_precision'],
                    (int)$row['data_scale'],
                    $default,
                    $row['nullable'] == 'N',
                    false,
                    in_array($name, $pks)
                );
            }

            yield $objectInfo;
        }
    }
}
