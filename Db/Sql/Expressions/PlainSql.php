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

namespace VV\Db\Sql\Expressions;

/**
 * Class PlainSql
 *
 * @package VV\Db\Sql\Expressions
 */
class PlainSql implements Expression
{
    use AliasFieldTrait;

    private string $sql;
    private array $params;

    /**
     * PlainSql constructor.
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
     * @return string
     */
    public function getSql(): string
    {
        return $this->sql;
    }

    /**
     * @return array
     */
    public function getParams(): array
    {
        return $this->params;
    }

    public function getExpressionId(): string
    {
        return $this->sql;
    }
}
