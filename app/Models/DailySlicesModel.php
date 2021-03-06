<?php
declare(strict_types=1);

namespace App\Models;


class DailySlicesModel extends AbstractModel
{
    const TABLE_PREFIX = 'daily_slices_';
    const TABLE = self::TABLE_PREFIX . '2017_01_01'; // Just for example

    public function __construct($connection)
    {
        parent::__construct($connection);

        $this->setTableFromTimestamp(time());
    }

    public function setTableFromTimestamp(int $timestamp)
    {
        $this->setTable(self::TABLE_PREFIX . date('Y_m_d', $timestamp));
        $this->createTableIfNotExists();
        return $this;
    }

    protected function createTable($name)
    {
        if ($this->shema()->hasTable($name)) {
            return;
        }

        $this->shema()->create($name, function ($table) {
            /** @var \Illuminate\Database\Schema\Blueprint $table */
            $table->increments('id');
            $table->unsignedSmallInteger('metric_id');
            $table->unsignedSmallInteger('slice_id');
            $table->integer('value');
            $table->unsignedSmallInteger('minute');

            $table->index(['metric_id', 'slice_id']);
            $table->index(['minute']);
            $table->unique(['metric_id', 'slice_id', 'minute']);
        });
    }

    public function getValues(int $metricId, int $sliceId): array
    {
        return $this->qb()
            ->where('metric_id', '=', $metricId)
            ->where('slice_id', '=', $sliceId)
            ->get(['minute', 'value']);
    }

    public function getAllByMetricId(int $metricId): array
    {
        return $this->qb()
            ->where('metric_id', '=', $metricId)
            ->get(['slice_id', 'minute', 'value']);
    }

    public function getMetricsBySlice(int $sliceId)
    {
        return $this->qb()->where('slice_id', $sliceId)
            ->groupBy('metric_id')
            ->pluck('metric_id');
    }

    public function createOrIncrement(int $metricId, int $sliceId, int $value, int $minute): int
    {
        // Check exist
        $id = $this->qb()->where('metric_id', $metricId)
            ->where('slice_id', $sliceId)
            ->where('minute', $minute)
            ->value('id');

        // Increment instead Insert
        if ($id) {
            $this->increment($id, 'value', $value);
            return $id;
        }

        $data = [
            'metric_id' => $metricId,
            'slice_id' => $sliceId,
            'value' => $value,
            'minute' => $minute,
        ];
        return $this->insert($data);
    }

    public function getTotals(int $timestamp, $metricId = null, bool $withNamesAndCategories = false): array
    {
        $minute = date('G', $timestamp) * 60 + date('i', $timestamp);
        $q = $this->qb();
        if ($withNamesAndCategories) {
            $q->selectRaw($this->getTable() . '.slice_id, slices.name, slices.category, sum(' . $this->getTable() . '.value) as value')
                ->join('slices', $this->getTable() . '.slice_id', '=', 'slices.id')
                ->groupBy($this->getTable() . '.slice_id', 'slices.name', 'slices.category');

        } else {
            $q->selectRaw($this->getTable() . '.slice_id, sum(' . $this->getTable() . '.value) as value')
                ->groupBy($this->getTable() . '.slice_id');
        }
        if ($metricId) {
            $q->where($this->getTable() . '.metric_id', '=', $metricId);
        }

        $q->where($this->getTable() . '.minute', '<', $minute);

        return $q->get();

    }

    public function getTotalsWithCategoryNames(int $timestamp):array
    {
        $minute = date('G', $timestamp) * 60 + date('i', $timestamp);
        $q = $this->qb()
            ->selectRaw('metrics.name as metric_name, slices.name, slices.category, sum(' . $this->getTable() . '.value) as value')
            ->join('slices', $this->getTable() . '.slice_id', '=', 'slices.id', 'left')
            ->join('metrics', $this->getTable() . '.metric_id', '=', 'metrics.id', 'left')
            ->where($this->getTable() . '.minute', '<', $minute)
            ->groupBy('metric_name', 'slices.name', 'slices.category');
        return $q->get();
    }

    public function dropTable($datePart)
    {
        $name = self::TABLE_PREFIX . $datePart;
        return $this->shema()->dropIfExists($name);
    }

    public function getDiff($metricId, $sliceId, $value, $minute) : float
    {
        $yesterdayValue = $this->qb()
            ->selectRaw('sum(value) as value')
            ->where('metric_id', $metricId)
            ->where('slice_id', $sliceId)
            ->where('minute', '<', $minute)
            ->value('value');

        if (!$yesterdayValue) {
            return (float) 0;
        }

        $diffPercent = (($value * 100) / $yesterdayValue) - 100;

        return (float)$diffPercent;
    }
}