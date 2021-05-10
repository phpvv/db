<?php declare(strict_types=1);
/** @noinspection PhpUnnecessaryFullyQualifiedNameInspection */

/*
 * This file is part of the VV package.
 *
 * (c) Volodymyr Sarnytskyi <v00v4n@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
namespace VV\Db\Sql;

use VV\Db\Sql\Clauses\DatasetFieldTrait;
use VV\Db\Sql\Expressions\Expression;

/**
 * Class InsertQuery
 *
 * @package VV\Db\Sql
 *
 * @property-read Clauses\DatasetClause      $dataset
 * @property-read Clauses\InsertFieldsClause $fields
 * @property-read Clauses\InsertValuesClause $values
 * @property-read mixed                      $insertedId     Execute query and return last insert id
 */
class InsertQuery extends ModificatoryQuery {

    use DatasetFieldTrait;

    public const C_DATASET = 0x01,
        C_FIELDS = 0x02,
        C_VALUES = 0x04,
        C_ONDUPKEY = 0x08,
        C_RETURN_INTO = 0x10,
        C_RETURN_INS_ID = 0x20,
        C_HINT = 0x40;

    private ?Clauses\InsertFieldsClause $fieldsClause = null;

    private ?Clauses\InsertValuesClause $valuesClause = null;

    private ?Clauses\DatasetClause $onDupKeyClause = null;

    private ?Clauses\InsertedIdClause $insertedIdClause = null;

    private int $execPerCount = 0;

    public function __get($var): mixed {
        return match ($var) {
            'insertedId' => $this->insertedId(),
            default => parent::__get($var),
        };
    }

    /**
     * Add `INTO` clause in sql
     *
     * @param string|\VV\Db\Model\Table $table
     * @param string|null               $alias
     *
     * @return $this
     */
    public function into($table, string $alias = null): static {
        return $this->table($table, $alias);
    }

    /**
     * @param string|\VV\Db\Sql\Expressions\Expression|Clauses\InsertFieldsClause ...$fields
     *
     * @return $this
     */
    public function fields(...$fields): static {
        $clause = $fields[0] instanceof Clauses\InsertFieldsClause
            ? $fields[0]
            : $this->createFieldsClause()->add(...$fields);

        return $this->setFieldsClause($clause);
    }

    /**
     * Add valuses
     *
     * @param string|Expression|\VV\Db\Param ...$values
     *
     * @return $this
     */
    public function values(...$values): static {
        if ($values[0] instanceof \VV\Db\Sql\SelectQuery) {
            $this->valuesClause()->clear()->add($values[0]);
        } else {
            $this->valuesClause()->add(...$values);
        }

        $this->perExec();

        return $this;
    }

    /**
     * @param $count
     *
     * @return $this
     */
    public function execPer($count): static {
        $this->execPerCount = $count;

        return $this;
    }

    /**
     * @return \VV\Db\Result|false
     */
    public function execPerFinish(): static {
        return $this->perExec(true);
    }

    /**
     * Add on duplicate key update clause
     *
     * @param string|\VV\Db\Sql\Expressions\Expression $field
     * @param mixed                                    $value
     *
     * @return $this
     *@todo Make it crossdb
     *
     */
    public function onDupKey($field, $value = false): static {
        $this->onDupKeyClause()->add(...func_get_args());

        return $this;
    }

    /**
     * Returns fieldsClause
     *
     * @return Clauses\InsertFieldsClause
     */
    public function fieldsClause(): ?Clauses\InsertFieldsClause {
        if (!$this->fieldsClause) {
            $this->setFieldsClause($this->createFieldsClause());
        }

        return $this->fieldsClause;
    }

    /**
     * Sets fieldsClause
     *
     * @param Clauses\InsertFieldsClause|null $fieldsClause
     *
     * @return $this
     */
    public function setFieldsClause(Clauses\InsertFieldsClause $fieldsClause = null): static {
        $this->fieldsClause = $fieldsClause;

        return $this;
    }

    /**
     * Clears fieldsClause property and returns previous value
     *
     * @return Clauses\InsertFieldsClause
     */
    public function clearFieldsClause(): ?Clauses\InsertFieldsClause {
        try {
            return $this->fieldsClause();
        } finally {
            $this->setFieldsClause(null);
        }
    }

    /**
     * Creates default fieldsClause
     *
     * @return Clauses\InsertFieldsClause
     */
    public function createFieldsClause(): Clauses\InsertFieldsClause {
        return new Clauses\InsertFieldsClause;
    }

    /**
     * Returns valuesClause
     *
     * @return Clauses\InsertValuesClause
     */
    public function valuesClause(): ?Clauses\InsertValuesClause {
        if (!$this->valuesClause) {
            $this->setValuesClause($this->createValuesClause());
        }

        return $this->valuesClause;
    }

    /**
     * Sets valuesClause
     *
     * @param Clauses\InsertValuesClause|null $valuesClause
     *
     * @return $this
     */
    public function setValuesClause(Clauses\InsertValuesClause $valuesClause = null): static {
        $this->valuesClause = $valuesClause;

        return $this;
    }

    /**
     * Clears valuesClause property and returns previous value
     *
     * @return Clauses\InsertValuesClause
     */
    public function clearValuesClause(): ?Clauses\InsertValuesClause {
        try {
            return $this->valuesClause();
        } finally {
            $this->setValuesClause(null);
        }
    }

    /**
     * Creates default valuesClause
     *
     * @return Clauses\InsertValuesClause
     */
    public function createValuesClause(): Clauses\InsertValuesClause {
        return new Clauses\InsertValuesClause;
    }

    /**
     * Returns onDupKeyClause
     *
     * @return Clauses\DatasetClause
     */
    public function onDupKeyClause(): ?Clauses\DatasetClause {
        if (!$this->onDupKeyClause) {
            $this->setOnDupKeyClause($this->createOnDupKeyClause());
        }

        return $this->onDupKeyClause;
    }

    /**
     * Sets onDupKeyClause
     *
     * @param Clauses\DatasetClause|null $onDupKeyClause
     *
     * @return $this
     */
    public function setOnDupKeyClause(Clauses\DatasetClause $onDupKeyClause = null): static {
        $this->onDupKeyClause = $onDupKeyClause;

        return $this;
    }

    /**
     * Clears onDupKeyClause property and returns previous value
     *
     * @return Clauses\DatasetClause
     */
    public function clearOnDupKeyClause(): ?Clauses\DatasetClause {
        try {
            return $this->onDupKeyClause();
        } finally {
            $this->setOnDupKeyClause(null);
        }
    }

    /**
     * Creates default onDupKeyClause
     *
     * @return Clauses\DatasetClause
     */
    public function createOnDupKeyClause(): Clauses\DatasetClause {
        return new Clauses\DatasetClause;
    }

    /**
     * Returns insertedIdClause
     *
     * @return Clauses\InsertedIdClause
     */
    public function insertedIdClause(): ?Clauses\InsertedIdClause {
        if (!$this->insertedIdClause) {
            $this->setInsertedIdClause($this->createReturnInsertedIdClause());
        }

        return $this->insertedIdClause;
    }

    /**
     * Creates default insertedIdClause
     *
     * @return Clauses\InsertedIdClause
     */
    public function createReturnInsertedIdClause(): Clauses\InsertedIdClause {
        return new Clauses\InsertedIdClause;
    }

    /**
     * @return int
     */
    public function execPerCount(): int {
        return $this->execPerCount;
    }

    /**
     * @param int $execPerCount
     *
     * @return $this
     */
    public function setExecPerCount(int $execPerCount): static {
        $this->execPerCount = $execPerCount;

        return $this;
    }

    /**
     * Executes(!) query and returns insertedId
     *
     * @param \VV\Db\Transaction|null $transaction
     *
     * @return mixed
     */
    public function insertedId(\VV\Db\Transaction $transaction = null): mixed {
        $retinsidClaues = $this->insertedIdClause();
        if ($retinsidClaues->isEmpty()) $retinsidClaues->set();

        return $this->exec($transaction)->insertedId();
    }

    /**
     *  Inits InsertedIdClause
     *
     * @param int|\VV\Db\Param|null    $type
     * @param int|null    $size
     * @param string|null $pk
     *
     * @return $this
     */
    public function initInsertedId($type = null, int $size = null, string $pk = null): static {
        $this->insertedIdClause()->set($type, $size, $pk);

        return $this;
    }

    /**
     * Sets insertedIdClause
     *
     * @param Clauses\InsertedIdClause|null $clause
     *
     * @return $this
     */
    protected function setInsertedIdClause(Clauses\InsertedIdClause $clause = null): static {
        $this->insertedIdClause = $clause;

        return $this;
    }

    protected function nonEmptyClausesMap(): array {
        return [
            self::C_DATASET => $this->datasetClause(),
            self::C_FIELDS => $this->fieldsClause(),
            self::C_VALUES => $this->valuesClause(),
            self::C_ONDUPKEY => $this->onDupKeyClause(),
            self::C_RETURN_INTO => $this->returnIntoClause(),
            self::C_RETURN_INS_ID => $this->insertedIdClause(),
            self::C_HINT => $this->hintClause(),
        ];
    }

    /**
     * @param bool $finish
     *
     * @return \VV\Db\Result|null
     */
    private function perExec(bool $finish = false): ?\VV\Db\Result {
        if (!$epc = $this->execPerCount()) return null;

        $valuesClause = $this->valuesClause();
        $cnt = count($valuesClause->items());

        if (!$cnt) return null;
        if (!$finish && $cnt < $epc) return null;

        try {
            return $this->exec();
        } finally {
            $this->clearValuesClause();
        }
    }
}
