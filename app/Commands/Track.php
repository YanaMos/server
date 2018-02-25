<?php
declare(strict_types=1);

namespace App\Commands;

use App\Events\Event;
use App\Events\Events;
use App\Events\Pack;
use App\Events\Slice;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class Track extends AbstractCommand
{
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        while (true) {
            $timeStart = time();
            $this->process();
            $timeDiff = time() - $timeStart;
            if ($timeDiff < 60) {
                sleep(60 - $timeDiff + 1);
            }
        }
    }

    public function process()
    {
        $startTime = microtime(true);
        $added = 0;

        do {
            $res = (int)$this->pack();
            $added += $res;

            $time = microtime(true) - $startTime;
            if ($time > 60) {
                $cnt = $this->redis->track_raw->sCard();
                if ($cnt) {
                    $this->out('Not enough time. ' . $cnt . ' left');
                    $this->redis->track_raw->del();
                    $this->out('[!] Deleted track_raw from redis');
                }
            }
        } while ($time < 60);
        $this->out('Packed: ' . $added);

        $saved = $this->flush();
        $this->out("Saved: $saved");
    }

    private function flush()
    {
        $packer = new Pack();
        $count = $packer->flushMetrics();
        $slicesCount = $packer->flushSlices();
        $this->out('Slices count: ' . $slicesCount);
        return $count;
    }

    private function pack()
    {
        $eventsJson = $this->redis->track_raw->sPop();
        if (!$eventsJson) {
            return 0;
        }

        $events = Events::fromJson($eventsJson);

        if (empty($events)) {
            return 0;
        }

        $pipe = $this->redis->getPipe();

        foreach ($events as $event) {
            /** @var Event $event */
            $pipe->zIncrBy('track_aggr_metrics', $event->getValue(), $event->getMetric());
            $pipe->zIncrBy('track_aggr_metric_totals', $event->getValue(), $event->getMetric());

            foreach ($event->getSlices() as $slice) {
                /**
                 * @var Slice $slice
                 */
                $slicesKey = implode('|', [$event->getMetric(), $slice->getName(), $slice->getValue()]);
                $pipe->zIncrBy('track_aggr_slices', $event->getValue(), $slicesKey);
                $pipe->zIncrBy('track_aggr_slice_totals', $event->getValue(), $slicesKey);
            }
        }

        $pipe->exec();

        return count($events);
    }
}