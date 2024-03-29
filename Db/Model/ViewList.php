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

namespace VV\Db\Model;

/**
 * Class ViewList
 *
 * @package VV\Db\Model
 * @method View|null get(string $name, array $prefixes = null): ?View
 */
abstract class ViewList extends ObjectList
{
    protected const SUB_NS = 'Views';
    protected const SUFFIX = 'View';
    protected const DFLT_PREFIXES = View::DFLT_PREFIXES;
}
