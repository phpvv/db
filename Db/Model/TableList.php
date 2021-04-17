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

/**
 * Class TableList
 *
 * @package VV\Db\Model
 * @method Table|null get(string $name, array $prefixes = null): ?Table
 */
abstract class TableList extends \VV\Db\Model\ObjectList {

    protected const SUBNS = 'Tables';
    protected const SUFFIX = 'Table';
    protected const DFLT_PREFIXES = Table::DFLT_PREFIXES;
}
