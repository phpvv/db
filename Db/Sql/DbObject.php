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

use VV\Db\Sql;

/**
 * Class Object
 *
 * @package VV\Db\Sql
 */
class DbObject implements Sql\Expression {

    use Sql\AliasFieldTrait;

    const NAME_RX = '[_a-zA-Z\$][\w]*';

    /**
     * @var string
     */
    private $name;

    /**
     * @var static
     */
    private $owner;

    protected function __construct(string $name = null, $owner = null) {
        if ($name) $this->setName($name);
        if ($owner) $this->setOwner($owner);
    }

    /**
     * @return string
     */
    public function name(): string {
        return $this->name;
    }

    public function resultName(): string {
        return $this->alias() ?: $this->name();
    }

    /**
     * @param string $name
     *
     * @return $this
     */
    public function setName(string $name): self {
        if (!$name) throw new \InvalidArgumentException('Name is empty');
        [$path, $alias] = static::parse($name);
        if (!$path) throw new \InvalidArgumentException('Incorrect name syntax');

        return $this->setParsedData($path, $alias);
    }

    /**
     * @return array
     */
    public function path(): array {
        $path = [];
        $cur = $this;
        do {
            array_unshift($path, $cur->name());
        } while ($cur = $cur->owner());

        return $path;
    }

    /**
     * @return DbObject
     */
    public function owner(): ?DbObject {
        return $this->owner;
    }

    /**
     * @param DbObject|string $owner
     *
     * @return $this
     */
    public function setOwner($owner = null): self {
        if ($owner) {
            if (!$owner instanceof static) {
                $owner = new static($owner);
            }

            $this->owner = $owner;
        } else {
            $this->owner = null;
        }

        return $this;
    }

    public function createChild($name): self {
        return new static($name, $this);
    }

    public function exprId(): string {
        return implode('-', $this->path());
    }

    /**
     * @param array       $names
     * @param string|null $alias
     *
     * @return $this
     */
    protected function setParsedData(array $names, string $alias = null): self {
        $this->setPlainName(array_pop($names));
        if ($alias) $this->as($alias);
        if ($names) {
            $owner = (new static)->setParsedData($names);
            $this->setOwner($owner);
        }

        return $this;
    }

    protected function setPlainName($name): self {
        $this->name = $name;

        return $this;
    }

    /**
     * @param string|static        $name
     * @param string|DbObject|null $dfltOwner
     * @param bool                 $parseAlias
     *
     * @return static|null
     */
    public static function create(string|self $name, string|self $dfltOwner = null, bool $parseAlias = true): ?self {
        if (!$name) throw new \InvalidArgumentException('DbObject Name is empty');
        if ($name instanceof static) return $name;

        [$path, $alias] = static::parse($name, $parseAlias);
        if (!$path) return null;

        $obj = (new static)->setParsedData($path, $alias);
        if ($dfltOwner && !$obj->owner()) $obj->setOwner($dfltOwner);

        return $obj;
    }

    /**
     * @param string $name
     * @param bool   $parseAlias
     *
     * @return array|null
     */
    public static function parse(string $name, bool $parseAlias = true): ?array {
        $namerx = static::NAME_RX;

        $alias = $parseAlias ? static::parseAlias($name, $name) : null;

        $names = \VV\splitNoEmpty($name, '\.', true);
        $outNames = [];
        foreach ($names as $i => $name) {
            if ($name == '*') {
                if ($i != count($names) - 1) return null;
                $outNames[] = $name;
                continue;
            }

            if (!preg_match("!^(`|)($namerx)\\1$!", $name, $m)) {
                return null;
            }

            $outNames[] = $m[2];
        }

        return [$outNames, $alias];
    }

    /**
     * @param string $name
     * @param string $nameWoAlias
     *
     * @return string|null
     */
    public static function parseAlias(string $name, &$nameWoAlias = null): ?string {
        $namerx = static::NAME_RX;

        /** @noinspection RegExpRepeatedSpace */
        $aliasRx = /** @lang RegExp */
            <<<RX
/
(?: \\s+ as)? # unnecessary 'as' keyword
\\s+
(`|)    # sql encloser
($namerx)    # alias
\\1          # sql encloser backreference
$
/xi
RX;
        $alias = null;
        $nameWoAlias = preg_replace_callback($aliasRx, function ($m) use (&$alias) {
            $alias = $m[2];
        }, $name);

        return $alias;
    }
}
