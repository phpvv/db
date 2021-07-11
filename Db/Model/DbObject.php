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
use VV\Db\Connection;

/**
 * Class Object
 *
 * @package VV\Db\Model
 */
abstract class DbObject
{
    public const DFLT_PREFIXES = [];
    protected const NAME = '';
    private Db $db;

    public function __construct(Db $db)
    {
        $this->db = $db;
    }

    /**
     * @return string
     */
    public function getName(): string
    {
        return static::NAME;
    }

    /**
     * @return Db
     */
    public function getDb(): Db
    {
        return $this->db;
    }

    /**
     * @return Connection
     */
    public function getConnection(): Connection
    {
        return $this->db->getConnection();
    }

    /**
     * Converts table name to alias: my_scheme.tbl_some_user_table -> sut
     *
     * @param string        $name
     * @param string[]|null $prefixes Will remove prefix from begin of name before the conversion
     *
     * @return string
     */
    public static function nameToAlias(string $name, array $prefixes = null): string
    {
        $name = self::trimPrefix($name, $prefixes);

        return strtolower(preg_replace('/(^|_)(\w)[^_]*/', '$2', $name));
    }

    /**
     * Name WithOut PreFiX
     *
     * @param string        $name
     * @param string[]|null $prefixes
     *
     * @return string
     */
    public static function trimPrefix(string $name, array $prefixes = null): string
    {
        foreach (($prefixes ?? static::DFLT_PREFIXES) as $pfx) {
            if (stripos($name, $pfx) === 0) {
                return substr($name, strlen($pfx));
            }
        }

        return $name;
    }
}
