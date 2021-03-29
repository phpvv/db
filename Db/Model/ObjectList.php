<?php declare(strict_types=1);

/*
 * This file is part of the VV package.
 *
 * (c) Volodymyr Sarnytskyi <v00v4n@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
namespace VV\Db\Model;

/**
 * Class ObjectList
 *
 * @package VV\Db\Model
 */
abstract class ObjectList {

    protected const SUBNS = '';

    protected const DFLT_PREFIXES = [];

    private \VV\Db $db;

    private string $ns;

    /** @var DbObject[] */
    private array $objects = [];

    final public function __construct(\VV\Db $db) {
        $this->db = $db;
        $this->ns = preg_replace('/\w+$/', '', get_class($this)) . $this->subns() . '\\';
    }

    final public function __get($camelName) {
        $item = $this->getByCamel($camelName);
        if (!$item) throw new \LogicException("Table '$camelName' not found");

        return $item;
    }

    /**
     * Get table object by name (tbl_my_table)
     *
     * @param string     $name
     * @param array|null $prefixes
     *
     * @return DbObject|null
     */
    public function get(string $name, array $prefixes = null): ?DbObject {
        $wopfx = DbObject::wopfx($name, $prefixes ?? static::DFLT_PREFIXES);
        $camelName = \VV\camelCase($wopfx);

        return $this->getByCamel($camelName);
    }

    /**
     * @param string $camelName
     *
     * @return DbObject|null
     */
    public function getByCamel(string $camelName): ?DbObject {
        $item = &$this->objects[$camelName];
        if ($item === null) {
            $class = $this->ns . ucfirst($camelName);
            if (!class_exists($class)) {
                $item = false;
            } else {
                $item = new $class($this->db);
            }
        }

        return $item ?: null;
    }

    /**
     * @return string
     */
    public function subns() {
        return static::SUBNS;
    }
}
