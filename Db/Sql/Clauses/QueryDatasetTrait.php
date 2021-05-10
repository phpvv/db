<?php declare(strict_types=1);

/*
 * This file is part of the VV package.
 *
 * (c) Volodymyr Sarnytskyi <v00v4n@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
namespace VV\Db\Sql\Clauses;

use JetBrains\PhpStorm\Pure;
use VV\Db\Sql\Expressions\Expression;

/**
 * Trait QueryDatasetTrait
 *
 * @package VV\Db\Sql\Clauses
 */
trait QueryDatasetTrait {

    private ?DatasetClause $datasetClause = null;

    /**
     * Add set
     *
     * @param iterable|string|Expression $field
     * @param mixed|null                 $value
     *
     * @return $this
     */
    public function set(iterable|string|Expression $field, mixed $value = null): static {
        $this->datasetClause()->add(...func_get_args());

        return $this;
    }

    /**
     * @return DatasetClause
     */
    public function datasetClause(): DatasetClause {
        if (!$this->datasetClause) {
            $this->setDatasetClause($this->createDatasetClause());
        }

        return $this->datasetClause;
    }

    /**
     * @param DatasetClause|null $clause
     *
     * @return $this
     */
    public function setDatasetClause(?DatasetClause $clause): static {
        $this->datasetClause = $clause;

        return $this;
    }

    /**
     * Clears datasetClause property and returns previous value
     *
     * @return DatasetClause
     */
    public function clearDatasetClause(): DatasetClause {
        try {
            return $this->datasetClause();
        } finally {
            $this->setDatasetClause(null);
        }
    }

    /**
     * @return DatasetClause
     */
    #[Pure]
    public function createDatasetClause(): DatasetClause {
        return new DatasetClause;
    }
}
