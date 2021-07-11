<?php declare(strict_types=1);

/*
 * This file is part of the VV package.
 *
 * (c) Volodymyr Sarnytskyi <v00v4n@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
namespace VV\Db\Sql;

use VV\Db\Sql\Predicates\Predicate;

/**
 * Class Item
 *
 * @package VV\Db\Sql\Clause\Condition
 */
class ConditionItem
{

    public const CONN_AND = 'AND',
        CONN_OR = 'OR';

    private Predicate $predicate;
    private string $connector = self::CONN_AND;

    /**
     * Item constructor.
     *
     * @param Predicate   $predicate
     * @param string|null $connector
     */
    public function __construct(Predicate $predicate, string $connector = null)
    {
        $this->predicate = $predicate;

        if ($connector) {
            $connector = strtoupper(trim($connector));
            if ($connector != self::CONN_AND && $connector != self::CONN_OR) {
                throw new \InvalidArgumentException('Wrong connector: neither "and" nor "or"');
            }

            $this->connector = $connector;
        };
    }

    /**
     * @return Predicate
     */
    public function predicate(): Predicate
    {
        return $this->predicate;
    }

    /**
     * @return string
     */
    public function connector(): string
    {
        return $this->connector;
    }
}
