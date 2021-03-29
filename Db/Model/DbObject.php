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

use VV\Db\Connection;

/**
 * Class Object
 *
 * @package VV\Db\Model
 */
abstract class DbObject {

    protected const NAME = '';

    public const DFLT_PREFIXES = [];

    private \VV\Db $db;

    public function __construct(\VV\Db $db) {
        $this->db = $db;
    }

    /**
     * @return string
     */
    public function name(): string {
        return static::NAME;
    }

    /**
     * @return \VV\Db
     */
    public function db(): \VV\Db {
        return $this->db;
    }

    /**
     * @return Connection
     */
    public function connection(): Connection {
        return $this->db->connection();
    }

    /**
     * Converts table name to alias: my_scheme.tbl_some_user_tabel -> sut
     *
     * @param string        $name
     * @param string[]|null $prefixes Will remove prefix from begin of name before the conversion
     *
     * @return string
     */
    public static function name2alias(string $name, array $prefixes = null): string {
        $name = self::wopfx($name, $prefixes);

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
    public static function wopfx(string $name, array $prefixes = null): string {
        foreach (($prefixes ?? static::DFLT_PREFIXES) as $pfx) {
            if (stripos($name, $pfx) === 0) {
                return substr($name, strlen($pfx));
            }
        }

        return $name;
    }
}
