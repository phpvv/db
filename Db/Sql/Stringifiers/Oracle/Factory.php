<?php

declare(strict_types=1);

/*
 * This file is part of the VV package.
 *
 * (c) Volodymyr Sarnytskyi <v00v4n@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
namespace VV\Db\Sql\Stringifiers\Oracle;

use VV\Db\Sql;

/**
 * Class Factory
 *
 * @package VV\Db\Sql\Stringifiers\Oracle
 */
class Factory implements \VV\Db\Sql\Stringifiers\Factory
{
    /**
     * @inheritDoc
     */
    public function createSelectStringifier(Sql\SelectQuery $query): SelectStringifier
    {
        return new SelectStringifier($query, $this);
    }

    /**
     * @inheritDoc
     */
    public function createInsertStringifier(Sql\InsertQuery $query): InsertStringifier
    {
        return new InsertStringifier($query, $this);
    }

    /**
     * @inheritDoc
     */
    public function createUpdateStringifier(Sql\UpdateQuery $query): UpdateStringifier
    {
        return new UpdateStringifier($query, $this);
    }

    /**
     * @inheritDoc
     */
    public function createDeleteStringifier(Sql\DeleteQuery $query): DeleteStringifier
    {
        return new DeleteStringifier($query, $this);
    }
}
