<?php

namespace App;
use Injectable\BaseInjectable;

/**
 * @property \App\Models\ModelFactory mysql
 * @property \App\Performance\Timer timer
 * @property \App\Redis\KeyLocator redis
 */

trait Injectable
{
    use BaseInjectable;
}