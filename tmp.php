<?php

require __DIR__ . '/vendor/autoload.php';


$data = json_encode([[
    'metric' => 'test',
    'value' => '3',
    'time' => time(),
    'slices' => [
        'some' => 'val',
        'other' => 12
    ],
]]);

var_dump($data);

$events = \App\Events\Events::fromJson($data);


var_dump(empty($events));