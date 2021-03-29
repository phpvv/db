<?php declare(strict_types=1);

/*
 * This file is part of the VV package.
 *
 * (c) Volodymyr Sarnytskyi <v00v4n@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
namespace VV\Db\Driver\QueryStringifiers;

use VV\Db\Sql;

/**
 * Interface Factory
 *
 * @package VV\Db\Driver\QueryStringifiers
 */
interface Factory {

    /**
     * @param Sql\SelectQuery $query
     *
     * @return SelectStringifier
     */
    public function createSelectStringifier(Sql\SelectQuery $query): SelectStringifier;

    /**
     * @param Sql\InsertQuery $query
     *
     * @return InsertStringifier
     */
    public function createInsertStringifier(Sql\InsertQuery $query): InsertStringifier;

    /**
     * @param Sql\UpdateQuery $query
     *
     * @return UpdateStringifier
     */
    public function createUpdateStringifier(Sql\UpdateQuery $query): UpdateStringifier;

    /**
     * @param Sql\DeleteQuery $query
     *
     * @return DeleteStringifier
     */
    public function createDeleteStringifier(Sql\DeleteQuery $query): DeleteStringifier;
}
