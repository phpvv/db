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

namespace VV\Db\Sql\Stringifiers;

/**
 * Class Plain
 *
 * @package VV\Db\Driver\Sql
 */
class SqlPart
{
    private string $sql;
    private array $params;

    /**
     * SqlPart constructor.
     *
     * @param string $sql
     * @param array  $params
     */
    public function __construct(string $sql, array $params = [])
    {
        $this->sql = $sql;
        $this->params = $params;
    }

    /**
     * @param array|null $params
     *
     * @return string
     */
    public function embed(?array &$params): string
    {
        if ($this->params) {
            if (!$params) {
                $params = [];
            }
            $params = array_merge($params, $this->params);
            // array_push($params, ...$this->params);
        }

        return $this->sql;
    }
}
