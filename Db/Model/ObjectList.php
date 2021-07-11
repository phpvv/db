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

use VV\Db;

use function VV\camelCase;

/**
 * Class ObjectList
 *
 * @package VV\Db\Model
 */
abstract class ObjectList
{
    protected const SUBNS = '';
    protected const SUFFIX = '';
    protected const DFLT_PREFIXES = [];

    private Db $db;
    private string $ns;
    /** @var DbObject[] */
    private array $objects = [];

    final public function __construct(Db $db)
    {
        $this->db = $db;
        $this->ns = preg_replace('/\w+$/', '', get_class($this)) . $this->subns() . '\\';
    }

    final public function __get($camelName)
    {
        $item = $this->getByCamelName($camelName);
        if (!$item) {
            throw new \LogicException("Table '$camelName' not found");
        }

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
    public function get(string $name, array $prefixes = null): ?DbObject
    {
        $wopfx = DbObject::trimPrefix($name, $prefixes ?? static::DFLT_PREFIXES);
        $camelName = camelCase($wopfx);

        return $this->getByCamelName($camelName);
    }

    /**
     * @param string $camelName
     *
     * @return DbObject|null
     */
    public function getByCamelName(string $camelName): ?DbObject
    {
        $item = &$this->objects[$camelName];
        if ($item === null) {
            $class = $this->ns . ucfirst($camelName) . $this->suffix();
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
    public function subns(): string
    {
        return static::SUBNS;
    }

    /**
     * @return string
     */
    public function suffix(): string
    {
        return static::SUFFIX;
    }
}
