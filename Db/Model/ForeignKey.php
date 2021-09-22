<?php /** @noinspection PhpPropertyOnlyWrittenInspection */
declare(strict_types=1);

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
 * Class Fk
 *
 * @package VV\Db\Model
 */
class ForeignKey
{
    private string $name;
    /** @var string[] */
    private array $fromColumns;
    private string $toTable;
    /** @var string[] */
    private array $toColumns;

    public function __construct(string $name, array $data)
    {
        $this->name = $name;

        static $props = ['fromColumns', 'toTable', 'toColumns'];
        foreach ($props as $k => $v) {
            $this->$v = $data[$k];
        }
    }

    /**
     * @return string[]
     */
    public function getFromColumns(): array
    {
        return $this->fromColumns;
    }

    /**
     * @return string
     */
    public function getToTable(): string
    {
        return $this->toTable;
    }

    /**
     * @return string[]
     */
    public function getToColumns(): array
    {
        return $this->toColumns;
    }
}
