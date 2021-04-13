<?php declare(strict_types=1);
/** @noinspection SqlResolve */

/*
 * This file is part of the VV package.
 *
 * (c) Volodymyr Sarnytskyi <v00v4n@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
namespace VV\Db\ModelGenerator;

use VV\Db\Driver\Driver;

class Generator {

    protected string $dir;

    protected string $mdl;

    protected \VV\Db $db;

    protected \VV\Db\Connection $connection;

    protected string $ns;

    protected string $dfltTop = <<<PHP
<?php declare(strict_types=1);

/** Created by VV Db Model Generator */
PHP;

    protected $dataObjectsGetterPhpdoc;

    protected $todel;

    public function __construct(\VV\Db $db) {
        // todo: accept \VV\Db\Connection and other options insteadof only \VV\Db instance ()
        $this->db = $db;

        $connection = $this->connection = $db->connection();
        if (!$connection->isConnected()) $connection->connect();

        $reflect = new \ReflectionObject($db);
        $this->ns = $reflect->getName();

        $this->dir = $dir = dirname($reflect->getFileName()) . \VV\DS . ($mdl = $reflect->getShortName()) . \VV\DS;

        $this->mdl = strtolower($mdl);

        if (!file_exists($dir)) mkdir($dir, 0777);

        $this->dataObjectsGetterPhpdoc = array_fill_keys($a = ['Table', 'View'], '');
        $this->todel = array_fill_keys($a, []);
        foreach ($this->todel as $k => &$v) {
            $d = "$dir$k/";
            if (file_exists($d)) {
                foreach (scandir($d) as $f) {
                    if ($f != '.' && $f != '..') $v[$f] = $d . $f;
                }
            }
        }
        unset($v);
    }

    protected function createStructBuilder(): StructBuilder {
        $dbms = $this->connection->dbms();
        switch ($dbms) {
            case Driver::DBMS_MYSQL:
                return new StructBuilder\Mysql;

            case Driver::DBMS_ORACLE:
                return new StructBuilder\Oracle;

            case Driver::DBMS_POSTGRES:
                return new StructBuilder\Postgres;

            default:
                throw new \RuntimeException('Not developed for db type: ' . $dbms);
        }
    }

    public function build() {
        echo "Added/updated classes:\r\n";

        $structBuilder = $this->createStructBuilder();
        foreach ($structBuilder->objectIterator($this->connection) as $objectInfo) {
            $this->processObject($objectInfo);
        }


        foreach ($this->dataObjectsGetterPhpdoc as $dataObjectType => $v) {
            // generate Tabel/View List class
            $className = $dataObjectType . 'List';

            $file = "$this->dir$className.php";
            file_put_contents($file, <<<PHP
$this->dfltTop
namespace $this->ns;

/**
 * Class $className
 *
$v */
class $className extends \\VV\\Db\\Model\\$className {

}
PHP
            );


            $className = $dataObjectType;
            $file = $this->dir . $className . ".php";
            if (!file_exists($file)) {
                file_put_contents($file, <<<PHP
$this->dfltTop
namespace $this->ns;

/**
 * Class $className
 */
class $className extends \\VV\\Db\\Model\\$className {

}
PHP
                );
            }
        }

        // remove excess files
        echo "\r\nRemoved files/directories:\r\n";
        foreach ($this->todel as $dataObjectType => $v) {
            foreach ($v as $f => $ff) {
                echo "\tMain/$dataObjectType/$f\r\n";
                \VV\Utils\Fs::rm($ff);
            }
        }
    }

    public static function buildTypeDecorator($map) {
        return function ($t) use ($map) {
            foreach ($map as $k => $v) {
                foreach ((array)$v as $rx) {
                    if (preg_match('/^' . $rx . '$/i', $t)) return $k;
                }
            }

            return 'CHR';
        };
    }


    protected function processObject(ObjectInfo $object) {
        $objType = $object->type();
        $classPth = $this->ns . '\\' . $objType;
        $tableWopfx = \VV\Db\Model\DataObject::wopfx($object->name());
        $name = \VV\StudlyCaps($tableWopfx);
        $fullname = "$classPth\\$name";

        echo "\t$fullname\r\n";

        // write fields
        $fieldsConstContent = "\n";
        $pkFields = [];
        foreach ($object->columns() as $k => $v) {
            $data = [];
            foreach ($v as $ck => $cv) {
                if (!is_int($ck)) continue;

                if (!$data) {
                    $data[] = "Field::T_$cv";
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

            $fieldsConstContent .= "        '$k' => [" . implode(', ', $data) . "],\n";
            if (!empty($v['pk'])) $pkFields[] = $k;
        }

        // write foreign keys
        $fkConstContent = "\n";
        foreach ($object->foreignKeys() as $k => $v) {
            $row = [
                "['" . implode("', '", array_values($v[0])) . "']",
                "'$v[1]'",
                "['" . implode("', '", $v[2]) . "']",
            ];

            $fkConstContent .= "        '$k' => [" . implode(', ', $row) . "],\n";
        }

        // write table
        $this->dataObjectsGetterPhpdoc[$objType] .= " * @property-read $objType\\$name \$" . lcfirst($name) . "\n";

        $extends = '\\' . $classPth;
        $pkConst = implode(', ', $pkFields);
        $pkFieldsConst = implode("', '", $pkFields);
        $alias = \VV\Db\Model\DataObject::name2alias($tableWopfx, []);

        $content = <<<EOL
$this->dfltTop
namespace $classPth;

use VV\Db\Model\Field;

class $name extends $extends {

    protected const NAME = '{$object->name()}';

    protected const PK = '$pkConst';

    protected const PK_FIELDS = ['$pkFieldsConst'];

    protected const DFLT_ALIAS = '$alias';

    protected \$fields = [$fieldsConstContent    ];

    protected \$foreignKeys = [$fkConstContent    ];
}
EOL;
        @mkdir($this->dir . $objType, 0777);
        $file = $this->dir . $objType . '/' . ($f = $name . '.php');
        unset($this->todel[$objType][$f]);

        file_put_contents($file, $content);
    }
}
