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
 * Class Postgres
 *
 * @package VV\Db\ModelGenerator\StructBuilder
 */
class Postgres implements \VV\Db\Model\Generator\StructBuilder
{

    public function objectIterator(\VV\Db\Connection $connection): iterable
    {
        $typed = ModelGenerator::buildTypeDecorator([
                'NUM' => ['.*int', 'decimal', 'integer', 'numeric', 'double', 'float', 'real'],
                'TEXT' => '.*text',
                'BLOB' => '.*blob',
                'DATETIME' => ['timestamp.*', 'datetime'],
                'DATE' => ['date', 'year'],
                'TIME' => 'time',
                'BOOL' => ['boolean', 'bool'],
            ]
        );

        $tableIter = $connection
            ->query("SELECT table_name FROM information_schema.tables WHERE table_schema = 'public' ORDER BY table_name")
            ->setFlags(\VV\Db::FETCH_NUM);

        foreach ($tableIter as [$table]) {
            $objectInfo = new ObjectInfo($table, 'Table');

            $pks = $connection->query(<<<SQL
SELECT c.column_name
FROM information_schema.table_constraints tc
         JOIN information_schema.constraint_column_usage AS ccu USING (constraint_schema, constraint_name)
         JOIN information_schema.columns AS c
             ON c.table_schema = tc.constraint_schema
                    AND tc.table_name = c.table_name
                    AND ccu.column_name = c.column_name
WHERE constraint_type = 'PRIMARY KEY' AND tc.table_name = '$table'
SQL
            )->assoc;


            $query = <<<SQL
SELECT
       column_name, data_type, character_maximum_length,
       numeric_precision, numeric_scale, column_default, is_nullable, numeric_precision_radix
FROM information_schema.columns
WHERE table_schema = 'public' and table_name = '$table'
SQL;

            $result = $connection->query($query);
            foreach ($result as $row) {
                // precision scale
                $precision = $row['numeric_precision'];
                $precisionRadix = $row['numeric_precision_radix'];
                if ($precisionRadix != 10) {
                    // todo: temporary solution
                    $precision = strlen((string)($precisionRadix ** $precision)) - 1;
                }

                $default = $row['column_default'];
                if ($default != null) {
                    $default = trim((string)$default);
                }


                $dataType = $row['data_type'];
                $type = $typed($dataType);
                $intSize = match ($dataType) {
                    'bigint' => 8,
                    'integer' => 4,
                    'smallint' => 2,
                    default => null,
                };

                $objectInfo->addColumn(
                    name: $name = $row['column_name'],
                    type: $type,
                    length: $row['character_maximum_length'],
                    intSize: $intSize,
                    precision: $precision,
                    scale: $row['numeric_scale'],
                    default: $default,
                    notnull: $row['is_nullable'] == 'NO',
                    unsigned: false,
                    inpk: in_array($name, $pks)
                );
            }


            $query = <<<SQL
                SELECT constraint_name
                FROM information_schema.table_constraints
                WHERE constraint_type = 'FOREIGN KEY' AND table_schema = 'public' AND table_name = '$table';
                SQL;

            $result = $connection->query($query, null, \VV\Db::FETCH_NUM);
            foreach ($result as [$name]) {
                $fromCols = $connection
                    ->query(
                        <<<SQL
                            SELECT column_name
                            FROM information_schema.key_column_usage
                            WHERE table_schema = 'public' and constraint_name = '$name';
                            SQL
                    )
                    ->assoc;

                /** @var string $toTable */
                $toTable = null;
                $toCols = [];
                $result = $connection
                    ->query(
                        <<<SQL
                            SELECT table_name, column_name
                            FROM information_schema.constraint_column_usage
                            WHERE table_schema = 'public' and constraint_name = '$name';
                            SQL,
                        null,
                        \VV\Db::FETCH_NUM
                    );

                foreach ($result as [$tbl, $col]) {
                    if (!$toTable) {
                        $toTable = $tbl;
                    } elseif ($tbl != $toTable) {
                        throw new \RuntimeException();
                    }

                    $toCols[] = $col;
                }

                $objectInfo->addForeignKey($name, $fromCols, $toTable, $toCols);
            }

            yield $objectInfo;
        }
    }
}
