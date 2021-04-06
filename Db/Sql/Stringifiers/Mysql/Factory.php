<?php declare(strict_types=1);

/*
 * This file is part of the phpvv package.
 *
 * (c) Volodymyr Sarnytskyi <v00v4n@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
namespace VV\Db\Sql\Stringifiers\Mysql;

use JetBrains\PhpStorm\Pure;
use VV\Db\Sql;

/**
 * Class Factory
 *
 * @package VV\Db\Sql\Stringifiers\Mysql
 */
class Factory implements \VV\Db\Sql\Stringifiers\Factory {


    /**
     * @inheritDoc
     */
    #[Pure]
    public function createSelectStringifier(Sql\SelectQuery $query): SelectStringifier {
        return new SelectStringifier($query, $this);
    }

    /**
     * @inheritDoc
     */
    #[Pure]
    public function createInsertStringifier(Sql\InsertQuery $query): InsertStringifier {
        return new InsertStringifier($query, $this);
    }

    /**
     * @inheritDoc
     */
    #[Pure]
    public function createUpdateStringifier(Sql\UpdateQuery $query): UpdateStringifier {
        return new UpdateStringifier($query, $this);
    }

    /**
     * @inheritDoc
     */
    #[Pure]
    public function createDeleteStringifier(Sql\DeleteQuery $query): DeleteStringifier {
        return new DeleteStringifier($query, $this);
    }
}
