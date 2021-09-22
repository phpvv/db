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

namespace VV\Db\Model\Generator;

use VV\Db;
use VV\Db\Connection;
use VV\Db\Driver\Driver;
use VV\Db\Model\DataObject;

class ModelGenerator
{

    private Connection $connection;
    private string $ns;
    private string $dir;
    private array $dataObjectsGetterPhpdoc;
    private array $toDel;

    private string $dfltTop = <<<CODE
        <?php

        /** Created by VV Db Model Generator */

        declare(strict_types=1);

        CODE;

    public function __construct(Db $db)
    {
        $connection = $this->connection = $db->getConnection();
        if (!$connection->isConnected()) {
            $connection->connect();
        }

        $reflect = new \ReflectionObject($db);
        $this->ns = $reflect->getName();

        $this->dir = $dir = dirname($reflect->getFileName()) . '/' . $reflect->getShortName() . '/';
        if (!file_exists($dir)) {
            mkdir($dir);
        }

        $this->dataObjectsGetterPhpdoc = array_fill_keys($a = ['Table', 'View'], '');
        $this->toDel = array_fill_keys($a, []);
        foreach ($this->toDel as $k => &$v) {
            $d = "$dir$k/";
            if (file_exists($d)) {
                foreach (scandir($d) as $f) {
                    if ($f != '.' && $f != '..') {
                        $v[$f] = $d . $f;
                    }
                }
            }
        }
        unset($v);
    }

    public function build()
    {
        echo "Added/updated classes:\r\n";

        $structBuilder = $this->createStructBuilder($this->connection);
        foreach ($structBuilder->objectIterator($this->connection) as $objectInfo) {
            $this->processObject($objectInfo);
        }


        foreach ($this->dataObjectsGetterPhpdoc as $dataObjectType => $v) {
            // generate Table/View List class
            $className = $dataObjectType . 'List';

            $file = "$this->dir$className.php";
            file_put_contents(
                $file,
                <<<CODE
                $this->dfltTop
                namespace $this->ns;

                /**
                 * Class $className
                 *
                 * @package $this->ns
                $v */
                class $className extends \\VV\\Db\\Model\\$className
                {
                }

                CODE
            );


            $className = $dataObjectType;
            $file = $this->dir . $className . ".php";
            if (!file_exists($file)) {
                file_put_contents(
                    $file,
                    <<<CODE
                    $this->dfltTop
                    namespace $this->ns;

                    /**
                     * Class $className
                     *
                     * @package $this->ns
                     */
                    class $className extends \\VV\\Db\\Model\\$className
                    {
                    }

                    CODE
                );
            }
        }

        // remove excess files
        echo "\r\nRemoved files/directories:\r\n";
        foreach ($this->toDel as $dataObjectType => $v) {
            foreach ($v as $f => $ff) {
                echo "\tMain/$dataObjectType/$f\r\n";
                @unlink($ff);
            }
        }
    }

    protected function createStructBuilder(Connection $connection): StructBuilder
    {
        return match ($connection->getDbmsName()) {
            Driver::DBMS_MYSQL => new StructBuilder\Mysql(),
            Driver::DBMS_ORACLE => new StructBuilder\Oracle(),
            Driver::DBMS_POSTGRES => new StructBuilder\Postgres(),
        };
    }

    protected function processObject(ObjectInfo $object)
    {
        $type = $object->getType();
        $tableWoPfx = DataObject::trimPrefix($object->getName());
        $name = DataObject::camelCase($tableWoPfx);
        $className = ucfirst($name) . $type;
        $relNs = $object->getTypePlural();
        $ns = "$this->ns\\$relNs";
        $fqcn = "$ns\\$className";

        echo "\t$fqcn\r\n";

        // write columns
        $columnConstContent = "\n";
        $pkColumns = [];
        foreach ($object->getColumns() as $k => $v) {
            $data = [];
            foreach ($v as $ck => $cv) {
                if (!is_int($ck)) {
                    continue;
                }

                if (!$data) {
                    $data[] = "Column::T_$cv";
                } elseif (is_int($cv) || is_float($cv)) {
                    $data[] = $cv;
                } elseif (is_bool($cv)) {
                    $data[] = $cv ? 'true' : 'false';
                } elseif ((string)$cv === '') {
                    $data[] = 'null';
                } else {
                    $data[] = "'" . str_replace("'", "\\'", $cv) . "'";
                }
            }

            $columnConstContent .= "        '$k' => [" . implode(', ', $data) . "],\n";
            if (!empty($v['pk'])) {
                $pkColumns[] = $k;
            }
        }

        // write foreign keys
        $fkConstContent = "\n";
        foreach ($object->getForeignKeys() as $k => $v) {
            $row = [
                "['" . implode("', '", array_values($v[0])) . "']",
                "'$v[1]'",
                "['" . implode("', '", $v[2]) . "']",
            ];

            $fkConstContent .= "        '$k' => [" . implode(', ', $row) . "],\n";
        }

        // write table
        $this->dataObjectsGetterPhpdoc[$type] .= " * @property-read $relNs\\$className \$$name\n";

        $pkConst = implode(', ', $pkColumns);
        $pkColumnConst = implode("', '", $pkColumns);
        $alias = DataObject::nameToAlias($tableWoPfx, []);

        [$phpDoc, $advTopContent, $advBottomContent] = $this->parseClassAdvContent($fqcn);


        $content = <<<CODE
            $this->dfltTop
            namespace $ns;

            use VV\Db\Model\Column;
            use $this->ns\\$type;

            {$phpDoc}class $className extends $type
            {
            $advTopContent    //region Auto-generated area
                protected const NAME = '{$object->getName()}';
                protected const PK = '$pkConst';
                protected const PK_COLUMNS = ['$pkColumnConst'];
                protected const DFLT_ALIAS = '$alias';
                protected const COLUMNS = [$columnConstContent    ];
                protected const FOREIGN_KEYS = [$fkConstContent    ];
                //endregion$advBottomContent
            }

            CODE;

        @mkdir($this->dir . $relNs);
        $file = $this->dir . $relNs . '/' . ($f = $className . '.php');
        unset($this->toDel[$type][$f]);

        file_put_contents($file, $content);
    }

    protected function parseClassAdvContent(string $fqcn): ?array
    {
        if (!class_exists($fqcn)) {
            return null;
        }

        $reflector = new \ReflectionClass($fqcn);
        $classLines = new \LimitIterator(
            new \SplFileObject($reflector->getFileName()),
            $start = $reflector->getStartLine() + 1,
            $reflector->getEndLine() - $start - 1
        );
        $phpDoc = $reflector->getDocComment();
        if ($phpDoc) {
            $phpDoc .= "\n";
        }

        $topLines = $bottomLines = [];
        $stage = 0;
        foreach ($classLines as $line) {
            $starts = fn($with) => str_starts_with(trim($line), $with);

            switch ($stage) {
                case 0:
                    if ($starts('protected const NAME =')) {
                        $stage = 1;
                        $lastIdx = count($topLines) - 1;
                        if ($lastIdx >= 0 && str_starts_with(trim($topLines[$lastIdx]), '//')) {
                            unset($topLines[$lastIdx]);
                        }
                        break;
                    }
                    $topLines[] = $line;
                    break;
                case 1:
                    if ($starts('protected const FOREIGN_KEYS =')) {
                        $stage = 2;
                    }
                    break;
                case 2:
                    if ($starts('];')) {
                        $stage = 3;
                    }
                    break;
                case 3:
                    $stage = 4;
                    if ($starts('//')) {
                        break;
                    }
                // no break
                case 4:
                    $bottomLines[] = $line;
            }
        }

        $lines2code = fn($lines) => $lines && array_filter($lines, fn($v) => trim($v))
            ? implode('', $lines)
            : '';

        $advTopContent = $lines2code($topLines);
        if ($advTopContent) {
            $advTopContent = '    ' . ltrim("$advTopContent");
        }

        $advBottomContent = $lines2code($bottomLines);
        if ($advBottomContent) {
            $advBottomContent = rtrim("\n$advBottomContent");
        }

        return [$phpDoc, $advTopContent, $advBottomContent];
    }

    public static function buildTypeDecorator($map): \Closure
    {
        return function ($t) use ($map) {
            foreach ($map as $k => $v) {
                foreach ((array)$v as $rx) {
                    if (preg_match('/^' . $rx . '$/i', $t)) {
                        return $k;
                    }
                }
            }

            return 'CHR';
        };
    }
}
