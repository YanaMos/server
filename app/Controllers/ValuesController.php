<?php

namespace App\Controllers;

use App\Models\DailyMetricsModel;
use App\Models\DailySlicesModel;
use Illuminate\Database\QueryException;
use Psr\Http\Message\ServerRequestInterface;

class ValuesController extends AbstractController
{
    public function minutes(ServerRequestInterface $request)
    {
        $queryParams = $request->getQueryParams();
        $metricId = isset($queryParams['metric_id']) ? (int)$queryParams['metric_id'] : null;
        $sliceId = isset($queryParams['slice_id']) ? (int)$queryParams['slice_id'] : null;
        $from = isset($queryParams['from']) ? new \DateTime($queryParams['from']) : new \DateTime('-1 day');
        $from->setTime(0, 0, 0);
        $to = isset($queryParams['to']) ? new \DateTime($queryParams['to']) : new \DateTime();
        $to->setTime(23, 59, 59);
        $interval = \DateInterval::createFromDateString('1 day');
        $period = new \DatePeriod($from, $interval, $to);

        if (!$metricId && !$sliceId) {
            throw new \InvalidArgumentException('Invalid metric_id(' . $metricId . ') or slice_id(' . $sliceId . ') value');
        }

        if ($sliceId) {
            $values = $this->getSliceValuesByMinutes($metricId, $sliceId, $period);
        } else {
            $values = $this->getMetricValuesByMinutes($metricId, $period);
        }

        return $this->jsonResponse(['values' => $values]);
    }

    public function days(ServerRequestInterface $request)
    {
        $queryParams = $request->getQueryParams();
        $metricId = isset($queryParams['metric_id']) ? (int)$queryParams['metric_id'] : null;
        $sliceId = isset($queryParams['slice_id']) ? (int)$queryParams['slice_id'] : null;
        $from = isset($queryParams['from']) ? new \DateTime($queryParams['from']) : new \DateTime('-30 day');
        $to = isset($queryParams['to']) ? new \DateTime($queryParams['to']) : new \DateTime();

        if (!$metricId && !$sliceId) {
            throw new \InvalidArgumentException('Invalid metric_id(' . $metricId . ') or slice_id(' . $sliceId . ') value');
        }

        if ($sliceId) {
            $values = $this->getSliceValuesByDays($metricId, $sliceId, $from, $to);
        } else {
            $values = $this->getMetricValuesByDays($metricId, $from, $to);
        }

        return $this->jsonResponse(['values' => $values]);
    }

    private function getMetricValuesByMinutes(int $metricId, \DatePeriod $period): array
    {
        $result = [];
        foreach ($period as $dt) {
            /**
             * @var \DateTime $dt
             */
            try {
                $result[$dt->format('Y-m-d')] = array_column($this->mysql->dailyMetrics
                    ->setTable(DailyMetricsModel::TABLE_PREFIX . $dt->format('Y_m_d'))
                    ->get($metricId), 'value', 'minute');
            } catch (QueryException $exception) {
                if ($exception->getCode() !== '42S02') { //table does not exists
                    throw $exception;
                }
            }
        }

        return $result;
    }

    private function getMetricValuesByDays(int $metricId, \DateTime $from = null, \DateTime $to = null): array
    {
        return array_column($this->mysql->monthlyMetrics
            ->getByMetricId($metricId, $from, $to), 'value', 'date');
    }

    private function getSliceValuesByMinutes(int $metricId, int $sliceId, \DatePeriod $period): array
    {
        $result = [];
        foreach ($period as $dt) {
            /**
             * @var \DateTime $dt
             */
            try {
                $result[$dt->format('Y-m-d')] = array_column($this->mysql->dailySlices
                    ->setTable(DailySlicesModel::TABLE_PREFIX . $dt->format('Y_m_d'))
                    ->getValues($metricId, $sliceId), 'value', 'minute');
            } catch (QueryException $exception) {
                if ($exception->getCode() !== '42S02') { //table does not exists
                    throw $exception;
                }
            }
        }

        return $result;
    }

    private function getSliceValuesByDays(
        int $metricId,
        int $sliceId,
        \DateTime $from = null,
        \DateTime $to = null
    ): array
    {
        return array_column($this->mysql->monthlySlices
            ->getValues($metricId, $sliceId, $from, $to), 'value', 'date');
    }
}