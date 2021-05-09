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

/**
 * Trait DatasetFieldTrait
 *
 * @package VV\Db\Sql\Clauses
 */
trait DatasetFieldTrait {

    private DatasetClause $datasetClause;

    /**
     * Add set
     *
     * @param string|array|\VV\Db\Sql\SelectQuery $field
     * @param mixed                               $value
     * @param bool                                $isExp
     *
     * @return $this
     */
    public function set($field, $value = false) {
        $this->datasetClause()->add(...func_get_args());

        return $this;
    }

    /**
     * @return DatasetClause
     */
    public function datasetClause() {
        if (!$this->datasetClause) {
            $this->setDatasetClause($this->createDatasetClause());
        }

        return $this->datasetClause;
    }

    /**
     * @param DatasetClause $datasetClause
     *
     * @return $this
     */
    public function setDatasetClause(DatasetClause $datasetClause) {
        $this->datasetClause = $datasetClause;

        return $this;
    }

    /**
     * Clears datasetClause property and returns previous value
     *
     * @return DatasetClause
     */
    public function clearDatasetClause() {
        try {
            return $this->datasetClause();
        } finally {
            $this->setDatasetClause(null);
        }
    }

    /**
     * @return DatasetClause
     */
    public function createDatasetClause() {
        return new DatasetClause;
    }
}
