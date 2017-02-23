<?php


namespace App\Models;


class DailyRawSlicesModel extends AbstractModel
{
    const TABLE_PREFIX = 'daily_raw_slices_';
    const TABLE = self::TABLE_PREFIX . '2017_01_01'; // Just for example

    public function __construct($queryBuilder)
    {
        parent::__construct($queryBuilder);

        $this->setTable(self::TABLE_PREFIX . date('Y_m_d'));
        $this->createTable($this->getTable());
    }

    private function createTable($name)
    {
        if ($this->shema()->hasTable($name)) {
            return;
        }

        $this->shema()->create($name, function ($table) {
            /** @var \Illuminate\Database\Schema\Blueprint $table */
            $table->increments('id');
            $table->unsignedSmallInteger('metric_id');
            $table->unsignedSmallInteger('slice_id');
            $table->float('value');
            $table->unsignedSmallInteger('minute');
            $table->index('metric_id');
        });
    }

    public function create(int $metricId, int $sliceId, float $value, string $time): int
    {
        $ts = strtotime($time);
        $minute = date('H', $ts) * 60 + date('i', $ts);
        return $this->insert([
            'metric_id' => $metricId,
            'slice_id' => $sliceId,
            'value' => $value,
            'minute' => $minute,
        ]);
    }

    public function getValues(int $metricId, int $sliceId): array
    {
        return $this->qb()
            ->where('metric_id', '=', $metricId)
            ->where('slice_id', '=', $sliceId)
            ->get(['minute', 'value']);
    }

    public function getAggregatedData(string $time = 'now', int $startId = 0): array
    {
        $ts = strtotime($time);
        $minutes = date('H', $ts) * 60 + date('i', $ts);
        return $this->qb()
            ->selectRaw('metric_id, minute, slice_id, sum(value) as value')
            ->where('id', '>=', $startId)
            ->where('minute', '<', $minutes)
            ->groupBy('metric_id')
            ->groupBy('slice_id')
            ->groupBy('minute')
            ->get();
    }
}