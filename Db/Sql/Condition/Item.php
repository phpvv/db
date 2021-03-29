<?php declare(strict_types=1);

/*
 * This file is part of the VV package.
 *
 * (c) Volodymyr Sarnytskyi <v00v4n@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
namespace VV\Db\Sql\Condition;

/**
 * Class Item
 *
 * @package VV\Db\Sql\Clause\Condition
 */
class Item {

    const CONN_OR = 'OR',
        CONN_AND = 'AND';

    /**
     * @var \VV\Db\Sql\Condition\Predicate
     */
    private $predicate;

    /**
     * @var string
     */
    private $connector = self::CONN_AND;

    /**
     * Item constructor.
     *
     * @param \VV\Db\Sql\Condition\Predicate $predicate
     * @param string                         $connector
     */
    public function __construct(\VV\Db\Sql\Condition\Predicate $predicate, $connector = null) {
        $this->setPredicate($predicate);
        if ($connector) $this->setConnector($connector);
    }


    /**
     * @return \VV\Db\Sql\Condition\Predicate
     */
    public function predicate() {
        return $this->predicate;
    }

    /**
     * @return string
     */
    public function connector() {
        return $this->connector;
    }

    /**
     * @param \VV\Db\Sql\Condition\Predicate $predicate
     *
     * @return $this
     */
    protected function setPredicate(\VV\Db\Sql\Condition\Predicate $predicate) {
        $this->predicate = $predicate;

        return $this;
    }

    /**
     * @param string $connector
     *
     * @return $this
     */
    protected function setConnector($connector) {
        $connector = strtoupper(trim($connector));
        if ($connector != self::CONN_AND && $connector != self::CONN_OR) {
            throw new \InvalidArgumentException('Wrong connector: neither "and" nor "or"');
        }

        $this->connector = $connector;

        return $this;
    }
}
