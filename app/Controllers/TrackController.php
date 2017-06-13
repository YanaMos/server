<?php

namespace App\Controllers;

use App\Biz\Event;
use App\Keys;
use Psr\Http\Message\ServerRequestInterface;

class TrackController extends AbstractController
{
    public function create()
    {
        $added = (int)$this->redis->track_raw->sAdd(file_get_contents('php://input'));
        return $this->jsonResponse(['createdEvents' => $added]);
    }

    public function createTest()
    {
        $data = gzcompress(json_encode([[
            'metric' => 'test',
            'value' => '3',
            'time' => time(),
            'slices' => [
                'some' => 'val',
                'other' => 12
            ],
        ]]));
        $added = (int)$this->redis->track_raw->sAdd($data);
        return $this->jsonResponse(['createdEvents' => $added]);
    }
}