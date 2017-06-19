<?php

namespace App\Commands\Raw;


use App\Commands\AbstractCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class Slices extends AbstractCommand
{
    const COUNTER_NAME = 'raw_slices';

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        while (1) {

            $time = date('Y-m-d H:i:s');
            $lastCounter = $this->mysql->dailyCounters->getValue(static::COUNTER_NAME);
            $startId = $lastCounter ? $lastCounter + 1 : 0;

            // Getting maxId bc no order in aggr result
            $maxId = $this->mysql->dailyRawSlices->getMaxIdForTime($time);
            if ($maxId < $startId) {
                $this->out('No new records in dailyRawSlices from startId ' . $startId);
                sleep(5);
                continue;
            }
            $this->mysql->dailyCounters->updateOrInsert(static::COUNTER_NAME, $maxId);

            // Getting grouped data form RAW
            $aggregatedData = $this->mysql->dailyRawSlices->getAggregatedData($time, $startId, $maxId);
            if (!$aggregatedData) {
                $this->out('No raw data in dailyRawSlices from startId ' . $startId);
                sleep(5);
                continue;
            }

            // Saving into aggr table
            $saved = 0;
            foreach ($aggregatedData as $row) {
                $res = false;
                try {
                    $row['minute'] = date('H') * 60 + date('i');
                    $res = $this->mysql->dailySlices->insert($row);
                    $this->mysql->dailySliceTotals->addValue($row['metric_id'], $row['slice_id'], $row['value']);
                } catch (\Exception $e) {
                    $this->out($e->getMessage());
                }

                if ($res) {
                    $saved++;
                }
            }

            $this->out("Saved {$saved} daily slices. MaxId: {$maxId}");

            sleep(5);
        }
    }
}