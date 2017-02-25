<?php
declare(strict_types = 1);

namespace App\Models;

abstract class AbstractModel
{
    const TABLE = null;
    private $tableName = null;
    private $queryBuilder = null;

    public function __construct($queryBuilder)
    {
        if (!static::TABLE) {
            throw new \Exception('Model ' . static::class . ' without TABLE constant');
        }
        $this->setTable(static::TABLE);
        $this->queryBuilder = $queryBuilder;
    }

    /**
     * @return \Illuminate\Database\Query\Builder
     */
    protected function qb()
    {
        $connection = $this->queryBuilder;
        $qb = new QueryBuilder(
            $connection,
            $connection->getQueryGrammar(),
            $connection->getPostProcessor()
        );

        return $qb->from($this->getTable());
    }

    /**
     * @return \Illuminate\Database\Schema\Builder
     */
    protected function shema()
    {
        return $this->queryBuilder->getSchemaBuilder();
    }

    public function setTable($name)
    {
        $this->tableName = $name;
        return $this;
    }

    public function getTable()
    {
        return $this->tableName;
    }

    // ------------- Base public functions below -----------------

    public function getById(int $primaryId): array
    {
        return $this->qb()->find($primaryId);
    }


    public function insert(array $data): int
    {
        $insertId = $this->qb()->insertGetId($data);
        if (!$insertId) {
            throw new \Exception('Error in creating record');
        }
        return $insertId;
    }

    public function getAll(): array
    {
        return $this->qb()->get();
    }
}