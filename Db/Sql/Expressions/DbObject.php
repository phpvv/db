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
 * Class DbObject
 *
 * @package VV\Db\Sql\Expressions
 */
class DbObject implements Expression
{
    use AliasFieldTrait;

    public const NAME_RX = '[_a-zA-Z\$][\w]*';

    private string $name;
    private ?DbObject $owner = null;

    protected function __construct(string $name = null, DbObject|string $owner = null)
    {
        if ($name) {
            $this->setName($name);
        }
        if ($owner) {
            $this->setOwner($owner);
        }
    }

    /**
     * @return string
     */
    public function getName(): string
    {
        return $this->name;
    }

    public function getResultName(): string
    {
        return $this->getAlias() ?: $this->getName();
    }

    /**
     * @param string $name
     *
     * @return $this
     */
    public function setName(string $name): static
    {
        if (!$name = trim($name)) {
            throw new \InvalidArgumentException('Name is empty');
        }
        [$path, $alias] = static::parse($name);
        if (!$path) {
            throw new \InvalidArgumentException('Incorrect name syntax');
        }

        return $this->setParsedData($path, $alias);
    }

    /**
     * @return array
     */
    public function getPath(): array
    {
        $path = [];
        $cur = $this;
        do {
            array_unshift($path, $cur->getName());
        } while ($cur = $cur->getOwner());

        return $path;
    }

    /**
     * @return DbObject|null
     */
    public function getOwner(): ?DbObject
    {
        return $this->owner;
    }

    /**
     * @param string|DbObject|null $owner
     *
     * @return $this
     */
    public function setOwner(DbObject|string $owner = null): static
    {
        if ($owner && !$owner instanceof static) {
            $owner = new static($owner);
        }
        $this->owner = $owner;

        return $this;
    }

    public function createChild($name): static
    {
        return new static($name, $this);
    }

    public function getExpressionId(): string
    {
        return implode('-', $this->getPath());
    }

    /**
     * @param array       $names
     * @param string|null $alias
     *
     * @return $this
     */
    protected function setParsedData(array $names, string $alias = null): static
    {
        $this->setPlainName(array_pop($names));
        if ($alias) {
            $this->as($alias);
        }
        if ($names) {
            $owner = (new static())->setParsedData($names);
            $this->setOwner($owner);
        }

        return $this;
    }

    protected function setPlainName($name): static
    {
        $this->name = $name;

        return $this;
    }

    /**
     * @param string|int|DbObject  $name
     * @param string|DbObject|null $defaultOwner
     * @param bool                 $parseAlias
     *
     * @return static|null
     */
    public static function create(
        string|int|self $name,
        string|self $defaultOwner = null,
        bool $parseAlias = true
    ): ?static {
        if ($name instanceof static) {
            return $name;
        }
        if ((string)$name == '') {
            throw new \InvalidArgumentException('DbObject Name is empty');
        }

        [$path, $alias] = static::parse((string)$name, $parseAlias);
        if (!$path) {
            return null;
        }

        $obj = (new static())->setParsedData($path, $alias);
        if ($defaultOwner && !$obj->getOwner()) {
            $obj->setOwner($defaultOwner);
        }

        return $obj;
    }

    /**
     * @param string $name
     * @param bool   $parseAlias
     *
     * @return array|null
     */
    public static function parse(string $name, bool $parseAlias = true): ?array
    {
        $nameRx = static::NAME_RX;

        $alias = $parseAlias ? static::parseAlias($name, $name) : null;

        $names = \VV\splitNoEmpty($name, '\.', true);
        $outNames = [];
        foreach ($names as $i => $name) {
            if ($name == '*') {
                if ($i != count($names) - 1) {
                    return null;
                }
                $outNames[] = $name;
                continue;
            }

            if (!preg_match("!^(`|)($nameRx)\\1$!", $name, $m)) {
                return null;
            }

            $outNames[] = $m[2];
        }

        return [$outNames, $alias];
    }

    /**
     * @param string      $name
     * @param string|null $nameWoAlias
     *
     * @return string|null
     */
    public static function parseAlias(string $name, string &$nameWoAlias = null): ?string
    {
        $nameRx = static::NAME_RX;

        /** @noinspection RegExpRepeatedSpace */
        $aliasRx = /** @lang RegExp */
            <<<RX
/
(?: \\s+ as)? # unnecessary 'as' keyword
\\s+
(`|)          # sql encloser
($nameRx)     # alias
\\1           # sql encloser backreference
$
/xi
RX;
        // $aliasRx = "/(?:\\s+as)?\\s+(`|)($nameRx)\\1\$/xi";

        $alias = null;
        $nameWoAlias = preg_replace_callback(
            $aliasRx,
            function ($m) use (&$alias) {
                $alias = $m[2];
            },
            $name
        );

        return $alias;
    }
}
