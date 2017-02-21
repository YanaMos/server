<?php


namespace App\Controllers;


use App\Biz\Event;

class TestController extends AbstractController
{
    public function server()
    {
        $event = new Event();

//        while (1) {
//            $event->save('Product.Some.Metric' . random_int(1, 100), random_int(23, 24322) / 1000, 'now');
//        }
        $event->save('Product.Some.Metric', 1.0, 'now', [
            'vendor' => 'Gmail',
            'site' => 'example.com'
        ]);

        $event->save('Product.Some.Metric', 1.0, 'now', [
            'vendor' => 'Yahoo',
            'site' => 'example2.com'
        ]);

        $event->save('Product.Some.Metric2', 1.0, 'now', [
            'vendor' => 'Gmail',
            'site' => 'example.com'
        ]);

        $event->save('Product.Some.Metric2', 1.0, 'now', [
            'vendor' => 'Yahoo',
            'site' => 'example2.com'
        ]);

        die;
    }
}