<?php

namespace App\Events;


class Event
{
    protected $metric;
    protected $value;
    protected $time;
    /**
     * @var Slice[]
     */
    protected $slices;
//'metric' => 'test',
//'value' => '3',
//'time' => time(),
//'slices' => [
//'some' => 'val',
//'other' => 12
//],
    public function __construct(string $metric, int $value, int $time, array $slices = [])
    {
        $this->metric = $metric;
        $this->value = $value;
        $this->time = $time;
        $this->slices = $slices;
    }

    public function getMetric(): string
    {
        return $this->metric;
    }

    public function getValue(): int
    {
        return $this->value;
    }

    public function getTime(): int
    {
        return $this->time;
    }

    public function getSlices(): array
    {
        return $this->slices;
    }

    public static function fromJson(string $json): Event
    {
        $data = json_decode($json, true);
        $slices = [];
        foreach ($data['slices'] as $name => $value){
            $slices[] = new Slice($name, $value);
        }
        return new Event($data['metric'], $data['value'], $data['time'], $slices);
    }
}