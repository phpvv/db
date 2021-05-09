<?php declare(strict_types=1);

/*
 * This file is part of the VV package.
 *
 * (c) Volodymyr Sarnytskyi <v00v4n@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
namespace VV\Db\Sql\Expressions;

/**
 * Class Plain
 *
 * @package VV\Db\Sql
 */
class PlainSql implements Expression {

    use AliasFieldTrait;

    private string $sql;
    private array $params;

    /**
     * @param string $sql
     * @param array  $params
     */
    public function __construct(string $sql, array $params = []) {
        $this->sql = $sql;
        $this->params = $params;
    }

    /**
     * @return array
     */
    public function params(): array {
        return $this->params;
    }

    /**
     * @return string
     */
    public function sql(): string {
        return $this->sql;
    }

    public function exprId(): string {
        return $this->sql;
    }
}
